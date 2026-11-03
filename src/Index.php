<?php

namespace Orbit;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Contracts\Orbit;
use Orbit\Contracts\SingleFile;
use Orbit\Exceptions\InvalidDriverException;
use RuntimeException;
use Throwable;

class Index
{
    /** @var array<string, true> */
    private array $ensured = [];

    /** @var array<class-string, Driver> */
    private array $drivers = [];

    private ?bool $cached = null;

    private bool $prepared = false;

    public function __construct(
        private DatabaseManager $db,
    ) {}

    public function ensure(Model&Orbit $model): void
    {
        $key = $this->key($model);

        if (isset($this->ensured[$key])) {
            return;
        }

        // Mark first: rebuilding uses the model, which asks for its connection again.
        $this->ensured[$key] = true;

        try {
            $this->prepare();

            if (! is_dir($this->directory($model))) {
                mkdir($this->directory($model), recursive: true);
            }

            $meta = $this->meta($model);

            if ($meta !== null && ($this->cached() || ($meta['schema'] === $this->signature($model) && ! $this->changed($model, $meta)))) {
                return;
            }

            $this->rebuild($model);
        } catch (Throwable $e) {
            unset($this->ensured[$key]);

            throw $e;
        }
    }

    public function write(Model&Orbit $model, mixed $previousKey = null): void
    {
        if ($this->driver($model) instanceof SingleFile) {
            $this->rewrite($model, function (array $records) use ($model, $previousKey) {
                if ($previousKey !== null) {
                    unset($records[$previousKey]);
                }

                $records[$model->getKey()] = $model->toOrbitAttributes();

                return $records;
            });

            return;
        }

        $path = $this->path($model, $model->getKey());

        $this->put($path, $this->driver($model)->compile($model->toOrbitAttributes()));
        $this->record($model, $path);

        if ($previousKey !== null && (string) $previousKey !== (string) $model->getKey()) {
            $this->delete($model, $previousKey);
        }
    }

    public function delete(Model&Orbit $model, mixed $key = null): void
    {
        if ($this->driver($model) instanceof SingleFile) {
            $this->rewrite($model, function (array $records) use ($model, $key) {
                unset($records[$key ?? $model->getKey()]);

                return $records;
            });

            return;
        }

        $path = $this->path($model, $key ?? $model->getKey());

        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException("Unable to delete Orbit file [{$path}].");
        }

        $this->record($model, $path);
    }

    /** @param  array<class-string<Model&Orbit>>  $models */
    public function cache(array $models): void
    {
        $this->clear();
        $this->prepare();

        $this->connection()->table('_orbit_meta')->insert(['key' => 'cached', 'value' => '1']);
        $this->cached = true;

        foreach ($models as $model) {
            $this->ensure(new $model);
        }
    }

    public function clear(): void
    {
        // Drop the tables rather than deleting the database, so long-running workers
        // that still have it open see the rebuild instead of a deleted file.
        if (is_file(config('orbit.paths.database'))) {
            $schema = $this->connection()->getSchemaBuilder();

            foreach (array_column($schema->getTables(), 'name') as $table) {
                $schema->drop($table);
            }
        }

        $this->ensured = [];
        $this->cached = null;
        $this->prepared = false;
    }

    private function rebuild(Model&Orbit $model): void
    {
        $connection = $this->connection();
        $schema = $connection->getSchemaBuilder();
        $table = $model->getTable();
        $driver = $this->driver($model);

        $connection->transaction(function () use ($model, $connection, $schema, $table, $driver) {
            $schema->dropIfExists($table);
            $schema->create($table, fn (Blueprint $blueprint) => $this->define($model, $blueprint));

            // One bad file or record shouldn't break every query on the model. It stays in
            // the manifest, so it's retried once it changes rather than on every request.
            $skip = fn (string $source, Throwable $e) => Log::warning("Orbit skipped {$source} because it could not be indexed: {$e->getMessage()}", ['exception' => $e]);

            foreach (array_keys($this->manifest($model)) as $file) {
                $path = $this->directory($model).DIRECTORY_SEPARATOR.$file;

                try {
                    $records = $this->records($model, $path);
                } catch (Throwable $e) {
                    $skip("[{$path}]", $e);

                    continue;
                }

                foreach ($records as $key => $attributes) {
                    try {
                        $attributes[$model->getKeyName()] ??= $this->keyFromName($model, (string) $key);

                        $connection->table($table)->insert($model->fromOrbitAttributes($attributes));
                    } catch (Throwable $e) {
                        $skip($driver instanceof SingleFile ? "record [{$key}] in [{$path}]" : "[{$path}]", $e);
                    }
                }
            }

            $this->store($model, [
                'schema' => $this->signature($model),
                'files' => $this->manifest($model),
            ]);
        });
    }

    /**
     * The records in a file, keyed by their key, or by their file name or key in the
     * file when they don't include it.
     *
     * @return array<array-key, mixed>
     */
    private function records(Model&Orbit $model, string $path): array
    {
        $driver = $this->driver($model);
        $contents = file_get_contents($path);

        if (! $driver instanceof SingleFile) {
            return [pathinfo($path, PATHINFO_FILENAME) => $driver->parse($contents)];
        }

        $records = [];

        foreach ($driver->parse($contents) as $key => $attributes) {
            $records[is_array($attributes) ? ($attributes[$model->getKeyName()] ?? $key) : $key] = $attributes;
        }

        return $records;
    }

    private function keyFromName(Model&Orbit $model, string $key): string
    {
        if ($model->getKeyType() === 'int' && ! ctype_digit($key)) {
            throw new RuntimeException(sprintf(
                'The record has no `%s`, and [%s] can\'t be used as the integer key of [%s]. Add the key to the record, or give the model a string key.',
                $model->getKeyName(),
                $key,
                $model::class,
            ));
        }

        return $key;
    }

    /** @param  callable(array<array-key, mixed>): array<array-key, mixed>  $change */
    private function rewrite(Model&Orbit $model, callable $change): void
    {
        $path = $this->file($model);

        // A file that can't be parsed throws here, so the write is rolled back rather
        // than replacing the file's records.
        $records = is_file($path) ? $this->records($model, $path) : [];

        $this->put($path, $this->driver($model)->compile($change($records)));
        $this->record($model, $path);
    }

    private function put(string $path, string $contents): void
    {
        $temporary = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        // Write to a temporary file and rename it so readers never see a partial file.
        if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException("Unable to write Orbit file [{$path}].");
        }
    }

    private function record(Model&Orbit $model, string $path): void
    {
        $meta = $this->meta($model);
        $file = basename($path);

        if ($meta === null) {
            return;
        }

        clearstatcache(true, $path);

        if (is_file($path)) {
            $meta['files'][$file] = $this->stamp($path);
        } else {
            unset($meta['files'][$file]);
        }

        $this->store($model, $meta);
    }

    private function define(Model&Orbit $model, Blueprint $table): void
    {
        $model->schema($table);

        if ($model->usesTimestamps()) {
            $table->timestamp($model->getCreatedAtColumn())->nullable();
            $table->timestamp($model->getUpdatedAtColumn())->nullable();
        }

        if (method_exists($model, 'getDeletedAtColumn')) {
            $table->softDeletes($model->getDeletedAtColumn());
        }

        $driver = $this->driver($model);

        if ($driver instanceof ModifiesSchema) {
            $driver->schema($table);
        }
    }

    private function signature(Model&Orbit $model): string
    {
        $connection = $this->connection();
        $connection->getSchemaBuilder(); // Loads the schema grammar that Blueprint needs.

        $blueprint = new Blueprint($connection, $model->getTable());

        $this->define($model, $blueprint);

        return md5(json_encode(array_map(fn ($column) => $column->getAttributes(), $blueprint->getColumns())));
    }

    /** @return array<string, string> */
    private function manifest(Model&Orbit $model): array
    {
        $directory = $this->directory($model);
        $extension = '.'.$this->driver($model)->extension();
        $single = $this->driver($model) instanceof SingleFile ? basename($this->file($model)) : null;
        $manifest = [];

        clearstatcache();

        foreach (scandir($directory) as $file) {
            if (($single === null ? str_ends_with($file, $extension) : $file === $single) && is_file($directory.DIRECTORY_SEPARATOR.$file)) {
                $manifest[$file] = $this->stamp($directory.DIRECTORY_SEPARATOR.$file);
            }
        }

        return $manifest;
    }

    // Modification times only change once per second, so an edit later in the same
    // second can keep the same time and size. Files modified that recently are
    // recorded with a hash of their contents too.
    private function stamp(string $path): string
    {
        $stamp = filemtime($path).':'.filesize($path);

        return filemtime($path) >= time() - 1 ? $stamp.':'.md5_file($path) : $stamp;
    }

    /** @param  array{schema: string, files: array<string, string>}  $meta */
    private function changed(Model&Orbit $model, array $meta): bool
    {
        $recorded = $meta['files'];
        $current = $this->manifest($model);

        if (count($current) !== count($recorded) || array_diff_key($current, $recorded) !== []) {
            return true;
        }

        $settled = false;

        foreach ($recorded as $file => $stamp) {
            if ($stamp === $current[$file]) {
                continue;
            }

            // A file recorded with its hash that is no longer recent: compare its
            // contents once, then record it without the hash.
            [$mtime, $size, $hash] = explode(':', $stamp) + [2 => null];

            if ($hash === null || "{$mtime}:{$size}" !== $current[$file] || md5_file($this->directory($model).DIRECTORY_SEPARATOR.$file) !== $hash) {
                return true;
            }

            $settled = true;
        }

        if ($settled) {
            $this->store($model, [...$meta, 'files' => $current]);
        }

        return false;
    }

    /** @return array{schema: string, files: array<string, string>}|null */
    private function meta(Model&Orbit $model): ?array
    {
        $value = $this->connection()->table('_orbit_meta')->where('key', $this->key($model))->value('value');

        return $value === null ? null : json_decode($value, true);
    }

    private function store(Model&Orbit $model, array $meta): void
    {
        $this->connection()->table('_orbit_meta')->upsert(
            ['key' => $this->key($model), 'value' => json_encode($meta)],
            ['key'],
        );
    }

    // A model can change its table and source at runtime, such as one per tenant, so
    // each combination is indexed and tracked on its own.
    private function key(Model&Orbit $model): string
    {
        return implode('|', [$model::class, $model->getTable(), $model->getOrbitSource()]);
    }

    private function cached(): bool
    {
        return $this->cached ??= $this->connection()->table('_orbit_meta')->where('key', 'cached')->exists();
    }

    private function prepare(): void
    {
        if ($this->prepared) {
            return;
        }

        $database = config('orbit.paths.database');

        if (! is_file($database)) {
            if (! is_dir(dirname($database))) {
                mkdir(dirname($database), recursive: true);
            }

            touch($database);
            $this->db->purge('orbit');
        }

        $this->connection()->statement('create table if not exists _orbit_meta (key text primary key, value text not null)');

        $this->prepared = true;
    }

    private function connection(): Connection
    {
        return $this->db->connection('orbit');
    }

    // The directory holding the model's files, or its single file.
    private function directory(Model&Orbit $model): string
    {
        $source = config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();

        return $this->driver($model) instanceof SingleFile ? dirname($source) : $source;
    }

    private function file(Model&Orbit $model): string
    {
        return config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource().'.'.$this->driver($model)->extension();
    }

    private function path(Model&Orbit $model, mixed $key): string
    {
        $key = (string) $key;

        if ($key === '' || strpbrk($key, "/\\\0") !== false) {
            throw new InvalidArgumentException("[{$key}] can't be used as an Orbit file name.");
        }

        return $this->directory($model).DIRECTORY_SEPARATOR.$key.'.'.$this->driver($model)->extension();
    }

    private function driver(Model&Orbit $model): Driver
    {
        if (isset($this->drivers[$model::class])) {
            return $this->drivers[$model::class];
        }

        $class = $model->getOrbitDriver();

        if (! class_exists($class)) {
            throw InvalidDriverException::make($class);
        }

        $driver = app($class);

        if (! $driver instanceof Driver) {
            throw InvalidDriverException::make($class);
        }

        return $this->drivers[$model::class] = $driver;
    }
}
