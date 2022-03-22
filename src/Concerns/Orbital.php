<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orbit\Drivers\FileDriver;
use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalUpdated;
use Orbit\Facades\Orbit;
use Orbit\Models\OrbitMeta;
use Orbit\Support;
use ReflectionClass;

trait Orbital
{
    protected static $orbit;

    public static function bootOrbital()
    {
        if (! static::enableOrbit()) {
            return;
        }

        static::ensureOrbitDirectoriesExist();

        $driver = Orbit::driver(static::getOrbitalDriver());
        $modelFile = (new ReflectionClass(static::class))->getFileName();

        if (
            Orbit::isTesting() ||
            filemtime($modelFile) > filemtime(Orbit::getDatabasePath()) ||
            $driver->shouldRestoreCache(static::getOrbitalPath()) ||
            ! static::resolveConnection()->getSchemaBuilder()->hasTable((new static())->getTable())
        ) {
            (new static())->migrate();
        }

        static::created(function (Model $model) {
            if ($model->callTraitMethod('shouldCreate', $model) === false) {
                return;
            }

            // We need to refresh the model so that we can get all of the columns
            // and default values from the SQLite cache.
            $model->refresh();

            $driver = Orbit::driver(static::getOrbitalDriver());

            $status = $driver->save(
                $model,
                $directory = static::generateOrbitalFilePathForModel($model)
            );

            if (static::getOrbitalPathPattern() !== null && $driver instanceof FileDriver) {
                $path = $driver->filepath($directory, $model->getKey());

                OrbitMeta::query()->updateOrCreate([
                    'orbital_type' => $model::class,
                    'orbital_key' => $model->getKey(),
                ], [
                    'file_path_read_from' => $path,
                ]);
            }

            event(new OrbitalCreated($model));

            return $status;
        });

        static::updated(function (Model $model) {
            if ($model->callTraitMethod('shouldUpdate', $model) === false) {
                return;
            }

            $driver = Orbit::driver(static::getOrbitalDriver());

            $status = $driver->save(
                $model,
                $directory = static::generateOrbitalFilePathForModel($model)
            );

            if (static::getOrbitalPathPattern() !== null && $driver instanceof FileDriver) {
                $path = $driver->filepath($directory, $model->getKey());
                $meta = OrbitMeta::forOrbital($model);

                if ($meta->file_path_read_from !== $path) {
                    (new Filesystem())->delete($meta->file_path_read_from);

                    $meta->update([
                        'file_path_read_from' => $path,
                    ]);
                }
            }

            event(new OrbitalUpdated($model));

            return $status;
        });

        static::deleted(function (Model $model) {
            if ($model->callTraitMethod('shouldDelete', $model) === false) {
                return;
            }

            $status = Orbit::driver(static::getOrbitalDriver())->delete(
                $model,
                static::generateOrbitalFilePathForModel($model)
            );

            event(new OrbitalDeleted($model));

            return $status;
        });
    }

    public static function schema(Blueprint $table)
    {
        //
    }

    public static function resolveConnection($connection = null)
    {
        if (! static::enableOrbit()) {
            return parent::resolveConnection($connection);
        }

        return static::$resolver->connection('orbit');
    }

    public function getConnectionName()
    {
        if (! static::enableOrbit()) {
            return parent::getConnectionName();
        }

        return 'orbit';
    }

    public function migrate()
    {
        $table = $this->getTable();

        /** @var \Illuminate\Database\Schema\Builder $schema */
        $schema = static::resolveConnection()->getSchemaBuilder();

        if ($schema->hasTable($table)) {
            $schema->drop($table);
        }

        /** @var \Illuminate\Database\Schema\Blueprint|null $blueprint */
        $blueprint = null;

        static::resolveConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) use (&$blueprint) {
            static::schema($table);

            $this->callTraitMethod('schema', $table);

            $driver = Orbit::driver(static::getOrbitalDriver());

            if (method_exists($driver, 'schema')) {
                $driver->schema($table);
            }

            if ($this->usesTimestamps()) {
                $table->timestamps();
            }

            $blueprint = $table;
        });

        $driver = Orbit::driver(static::getOrbitalDriver());
        $columns = $schema->getColumnListing($table);

        $driver->all(static::getOrbitalPath())
            ->filter()
            ->map(function ($row) use ($columns, $blueprint) {
                $newRow = collect($row)
                    ->filter(fn ($_, $key) => in_array($key, $columns))
                    ->map(function ($value, $key) {
                        $this->setAttribute($key, $value);

                        return $this->attributes[$key];
                    })
                    ->toArray();

                if (array_key_exists('file_path_read_from', $row) && static::getOrbitalPathPattern() !== null) {
                    OrbitMeta::query()->updateOrCreate([
                        'orbital_type' => $this::class,
                        'orbital_key' => $this->getKey(),
                    ], [
                        'file_path_read_from' => $row['file_path_read_from'],
                    ]);
                }

                foreach ($columns as $column) {
                    if (array_key_exists($column, $newRow)) {
                        continue;
                    }

                    $definition = $blueprint->orbitGetColumn($column);

                    if ($definition->default) {
                        $newRow[$column] = $definition->default;
                    } elseif ($definition->nullable) {
                        $newRow[$column] = null;
                    }
                }

                return $newRow;
            })
            ->chunk(100)
            ->each(fn (Collection $chunk) => static::insert($chunk->toArray()));
    }

    protected static function getOrbitalDriver()
    {
        return property_exists(static::class, 'driver') ? static::$driver : null;
    }

    protected static function ensureOrbitDirectoriesExist()
    {
        if (! static::enableOrbit()) {
            return;
        }

        $fs = new Filesystem();

        $fs->ensureDirectoryExists(
            static::getOrbitalPath()
        );

        $fs->ensureDirectoryExists(
            \config('orbit.paths.cache')
        );

        $database = Orbit::getDatabasePath();

        if (! $fs->exists($database) && $database !== ':memory:') {
            $fs->put($database, '');
        }
    }

    public static function enableOrbit(): bool
    {
        return true;
    }

    public static function getOrbitalName()
    {
        return (string) Str::of(class_basename(static::class))->snake()->lower()->plural();
    }

    public static function getOrbitalPath()
    {
        return \config('orbit.paths.content') . DIRECTORY_SEPARATOR . static::getOrbitalName();
    }

    public static function getOrbitalPathPattern(): ?string
    {
        return null;
    }

    public static function generateOrbitalFilePathForModel(Model $model)
    {
        if (static::getOrbitalPathPattern() === null) {
            return static::getOrbitalPath();
        }

        $pattern = static::getOrbitalPathPattern();
        $path = static::getOrbitalPath() . DIRECTORY_SEPARATOR . Support::buildPathForPattern($pattern, $model);

        (new Filesystem())->ensureDirectoryExists($path);

        return $path;
    }

    public function callTraitMethod(string $method, ...$args)
    {
        $result = null;

        foreach (class_uses_recursive(static::class) as $trait) {
            $methodToCall = $method . class_basename($trait);

            if (method_exists($this, $methodToCall)) {
                $result = $this->{$methodToCall}(...$args);
            }
        }

        return $result;
    }
}
