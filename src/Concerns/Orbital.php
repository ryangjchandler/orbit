<?php

namespace Orbit\Concerns;

use Facade\FlareClient\Stacktrace\File;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Connectors\ConnectionFactory;
use Orbit\Facades\Orbit;

trait Orbital
{
    protected static $orbital;

    public static function bootOrbital()
    {
        static::ensureOrbitalDirectoriesExists();
        static::setSqliteConnection();

        (new static)->migrate();
    }

    public function migrate()
    {

    }

    public static function resolveConnection($connection = null)
    {
        return static::$orbital;
    }

    protected static function setSqliteConnection()
    {
        static::$orbital = app(ConnectionFactory::class)->make([
            'driver' => 'sqlite',
            'database' => static::shouldCacheWithOrbit()
                ? Orbit::getDatabasePath()
                : ':memory:',
        ]);
    }

    public static function ensureOrbitalDirectoriesExists()
    {
        $fs = new Filesystem;

        $fs->ensureDirectoryExists(
            config('orbit.paths.content')
        );

        $fs->ensureDirectoryExists(
            config('orbit.paths.content') . DIRECTORY_SEPARATOR . static::getOrbitalName()
        );

        $fs->ensureDirectoryExists(
            config('orbit.paths.cache')
        );

        $database = Orbit::getDatabasePath();

        if (! $fs->exists($database)) {
            $fs->put($database, null);
        }
    }

    public static function getOrbitalName()
    {
        return (string) Str::of(class_basename(static::class))->lower()->kebab();
    }

    public static function shouldCacheWithOrbit()
    {
        return true;
    }
}
