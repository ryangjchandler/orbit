<?php

namespace Orbit\Concerns;

use Orbit\Facades\Orbit;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

trait Orbital
{
    public static function bootOrbital()
    {
        static::ensureOrbitDirectoriesExist();
    }

    protected static function ensureOrbitDirectoriesExist()
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
            $fs->put($database, '');
        }
    }

    public static function getOrbitalName()
    {
        return (string) Str::of(class_basename(static::class))->lower()->snake()->plural();
    }
}
