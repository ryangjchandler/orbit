<?php

namespace Orbit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Orbit\Contracts\Driver;
use Orbit\Facades\Orbit;
use ReflectionClass;

/** @internal */
final class Support
{
    public static function generateFilename(Model $object, OrbitOptions $options, Driver $driver): string
    {
        $pattern = app()->call($options->getFilenameGenerator());

        return Str::of($pattern)
            ->explode('/')
            ->map(static function (string $part): string {
                return trim($part, '{}');
            })
            ->map(static function (string $part) use ($object): string {
                if (method_exists($object, $part)) {
                    return $object->{$part}();
                }
            })
            ->implode('/') . '.' . $driver->extension();
    }

    public static function callTraitMethods(Model $object, string $prefix, array $args = []): void
    {
        $called = [];

        foreach (class_uses_recursive($object) as $trait) {
            $method = $prefix . class_basename($trait);

            if (! method_exists($object, $method) || in_array($method, $called)) {
                continue;
            }

            $object->{$method}(...$args);

            $called[] = $method;
        }
    }

    public static function fileNeedsToBeSeeded(string $path, string $modelClass): bool
    {
        $changedTime = filemtime($path);
        $modelFile = (new ReflectionClass($modelClass))->getFileName();

        return $changedTime > filemtime($modelFile) || $changedTime > filemtime(Orbit::getCachePath());
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass */
    public static function modelNeedsMigration(string $modelClass): bool
    {
        $modelFile = (new ReflectionClass($modelClass))->getFileName();

        if (App::environment('testing')) {
            return true;
        }

        if (filemtime($modelFile) > filemtime(Orbit::getCachePath())) {
            return true;
        }

        $table = (new $modelClass())->getTable();

        if (! $modelClass::resolveConnection()->getSchemaBuilder()->hasTable($table)) {
            return true;
        }

        return false;
    }
}
