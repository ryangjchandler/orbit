<?php

namespace Orbit\Concerns;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Facades\Orbit;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalUpdated;
use ReflectionClass;

trait Orbital
{
    protected static $orbit;

    public static function bootOrbital()
    {
        static::ensureOrbitDirectoriesExist();

        $driver = Orbit::driver(static::getOrbitalDriver());
        $modelFile = (new ReflectionClass(static::class))->getFileName();

        if (
            Orbit::isTesting() ||
            filemtime($modelFile) > filemtime(Orbit::getDatabasePath()) ||
            $driver->shouldRestoreCache(static::getOrbitalPath()) ||
            ! static::resolveConnection()->getSchemaBuilder()->hasTable((new static)->getTable())
        ) {
            (new static)->migrate();
        }

        static::created(function (Model $model) {
            if ($model->callTraitMethod('shouldCreate', $model) === false) {
                return;
            }

            $status = Orbit::driver(static::getOrbitalDriver())->save(
                $model,
                static::getOrbitalPath()
            );

            event(new OrbitalCreated($model));

            return $status;
        });

        static::updated(function (Model $model) {
            if ($model->callTraitMethod('shouldUpdate', $model) === false) {
                return;
            }

            $status = Orbit::driver(static::getOrbitalDriver())->save(
                $model,
                static::getOrbitalPath()
            );

            event(new OrbitalUpdated($model));

            return $status;
        });

        static::deleted(function (Model $model) {
            if ($model->callTraitMethod('shouldDelete', $model) === false) {
                return;
            }

            $status = Orbit::driver(static::getOrbitalDriver())->delete(
                $model,
                static::getOrbitalPath()
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
        return static::$resolver->connection('orbit');
    }

    public function getConnectionName()
    {
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

        static::resolveConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            static::schema($table);

            $this->callTraitMethod('schema', $table);

            $driver = Orbit::driver(static::getOrbitalDriver());

            if (method_exists($driver, 'schema')) {
                $driver->schema($table);
            }

            if ($this->usesTimestamps()) {
                $table->timestamps();
            }
        });

        $driver = Orbit::driver(static::getOrbitalDriver());
        $columns = $schema->getColumnListing($table);

        $driver->all(static::getOrbitalPath())
            ->filter()
            ->map(function ($row) use ($columns) {
                $row = collect($row)
                    ->filter(fn ($_, $key) => in_array($key, $columns))
                    ->map(function ($value, $key) {
                        $this->setAttribute($key, $value);

                        return $this->attributes[$key];
                    })
                    ->toArray();

                foreach ($columns as $column) {
                    if (! array_key_exists($column, $row)) {
                        $row[$column] = null;
                    }
                }

                return $row;
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
        $fs = new Filesystem;

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

    public static function getOrbitalName()
    {
        return (string) Str::of(class_basename(static::class))->snake()->lower()->plural();
    }

    public static function getOrbitalPath()
    {
        return \config('orbit.paths.content') . DIRECTORY_SEPARATOR . static::getOrbitalName();
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
