<?php

namespace Orbit\Concerns;

use Facade\FlareClient\Stacktrace\File;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Facades\Orbit;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

trait Orbital
{
    protected static $orbit;

    protected static $driver = null;

    public static function bootOrbital()
    {
        static::ensureOrbitDirectoriesExist();
        static::setSqliteConnection();

        $driver = Orbit::driver(static::getOrbitalDriver());

        if ($driver->shouldRestoreCache(static::getOrbitalPath())) {
            (new static)->migrate();
        }
    }

    public static function getOrbitalSchema(Blueprint $table)
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

        static::resolveConnection()->getSchemaBuilder()->drop($table);

        static::resolveConnection()->getSchemaBuilder()->create($table, function (Blueprint $table) {
            static::getOrbitalSchema($table);
        });

        $driver = Orbit::driver(static::getOrbitalDriver());

        $driver->all(static::getOrbitalPath())->each(function (array $row) {
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
            config('orbit.paths.cache')
        );

        $database = Orbit::getDatabasePath();

        if (! $fs->exists($database)) {
            $fs->put($database, '');
        }
    }

    public static function getOrbitalName()
    {
        return (string) Str::of(class_basename(static::class))->lower()->snake()->plural();
    }

    public static function getOrbitalPath()
    {
        return config('orbit.paths.content') . DIRECTORY_SEPARATOR . static::getOrbitalName();
    }
}
