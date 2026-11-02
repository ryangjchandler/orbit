<?php

namespace Orbit;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Contracts\Orbit;
use Orbit\Exceptions\InvalidDriverException;
use RuntimeException;
use Throwable;

class Index
{
    /** @var array<class-string, true> */
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
        if (isset($this->ensured[$model::class])) {
            return;
        }

        // Mark first: rebuilding uses the model, which asks for its connection again.
        $this->ensured[$model::class] = true;

        try {
            $this->prepare();

            if (! is_dir($this->directory($model))) {
                mkdir($this->directory($model), recursive: true);
            }

            $meta = $this->meta($model);

            if ($meta !== null && ($this->cached() || ($meta['schema'] === $this->signature($model) && $meta['files'] == $this->manifest($model)))) {
                return;
            }

            $this->rebuild($model);
        } catch (Throwable $e) {
            unset($this->ensured[$model::class]);

            throw $e;
        }
    }

    public function write(Model&Orbit $model): void
    {
        $keyName = $model->getKeyName();

        if ($model->wasChanged($keyName) && $model->getOriginal($keyName) !== null) {
            $this->delete($model, $model->getOriginal($keyName));
        }

        $path = $this->path($model, $model->getKey());
        $temporary = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        // Write to a temporary file and rename it so readers never see a partial file.
        if (file_put_contents($temporary, $this->driver($model)->compile($model->toOrbitAttributes())) === false || ! rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException("Unable to write Orbit file [{$path}].");
        }

        $this->record($model, $path);
    }

    public function delete(Model&Orbit $model, mixed $key = null): void
    {
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
        $this->db->purge('orbit');

        $database = config('orbit.paths.database');

        foreach ([$database, "{$database}-journal", "{$database}-wal", "{$database}-shm"] as $file) {
            if (is_file($file)) {
                unlink($file);
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

            foreach (array_keys($this->manifest($model)) as $file) {
                $path = $this->directory($model).DIRECTORY_SEPARATOR.$file;

                try {
                    $attributes = $driver->parse(file_get_contents($path));
                    $attributes[$model->getKeyName()] ??= pathinfo($file, PATHINFO_FILENAME);

                    $connection->table($table)->insert($model->fromOrbitAttributes($attributes));
                } catch (Throwable $e) {
                    throw new RuntimeException("Unable to index Orbit file [{$path}]: {$e->getMessage()}", previous: $e);
                }
            }

            $this->store($model, [
                'schema' => $this->signature($model),
                'files' => $this->manifest($model),
            ]);
        });
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
        $manifest = [];

        clearstatcache();

        foreach (scandir($directory) as $file) {
            if (str_ends_with($file, $extension) && is_file($directory.DIRECTORY_SEPARATOR.$file)) {
                $manifest[$file] = $this->stamp($directory.DIRECTORY_SEPARATOR.$file);
            }
        }

        return $manifest;
    }

    private function stamp(string $path): string
    {
        return filemtime($path).':'.filesize($path);
    }

    /** @return array{schema: string, files: array<string, string>}|null */
    private function meta(Model&Orbit $model): ?array
    {
        $value = $this->connection()->table('_orbit_meta')->where('key', $model::class)->value('value');

        return $value === null ? null : json_decode($value, true);
    }

    private function store(Model&Orbit $model, array $meta): void
    {
        $this->connection()->table('_orbit_meta')->upsert(
            ['key' => $model::class, 'value' => json_encode($meta)],
            ['key'],
        );
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

    private function directory(Model&Orbit $model): string
    {
        return config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();
    }

    private function path(Model&Orbit $model, mixed $key): string
    {
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
