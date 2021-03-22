<?php

namespace Orbit\Concerns;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Facades\Orbit;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Orbit\Events\OrbitalCreated;
use Orbit\Events\OrbitalDeleted;
use Orbit\Events\OrbitalUpdated;
use ReflectionClass;

trait Orbital
{
    protected static $orbit;

    protected static $driver = null;

    public static function bootOrbital()
    {
        static::ensureOrbitDirectoriesExist();
        static::setSqliteConnection();

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

            OrbitalCreated::dispatch($model);

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

            OrbitalUpdated::dispatch($model);

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

            OrbitalDeleted::dispatch($model);

            return $status;
        });
    }

    public static function schema(Blueprint $table)
    {
        //
    }

    public static function resolveConnection($connection = null)
    {
        return static::$orbit;
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

            if ($this->usesTimestamps()) {
                $table->timestamps();
            }
        });

        $driver = Orbit::driver(static::getOrbitalDriver());

        $driver->all(static::getOrbitalPath())->each(function ($row) {
            foreach ($row as $key => $value) {
                $this->setAttribute($key, $value);

                $row[$key] = $this->attributes[$key];
            }

            static::insert($row);
        });
    }

    protected static function getOrbitalDriver()
    {
        return static::$driver;
    }

    protected static function setSqliteConnection()
    {
        static::$orbit = app(ConnectionFactory::class)->make([
            'driver' => 'sqlite',
            'database' => Orbit::getDatabasePath(),
        ]);
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
            $methodToCall = $method . Str::of($trait)->classBasename();

            if (method_exists($this, $methodToCall)) {
                $result = $this->{$methodToCall}(...$args);
            }
        }

        return $result;
    }
}
