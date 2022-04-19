<?php

namespace Orbit;

use Carbon\Carbon;
use DateTimeInterface;
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
            ->map(static function (string $part) use ($object): string {
                if (! Str::startsWith($part, '{') && ! Str::endsWith($part, '}')) {
                    return $part;
                }

                [$source, $arg] = Str::of($part)
                    ->trim('{}')
                    ->whenContains(':',
                        fn ($str) => $str->explode(':', 2),
                        fn ($str) => [$str->toString(), null],
                    );

                if (method_exists($object, $source)) {
                    return $object->{$source}();
                }

                $property = $object->{$source};

                if ($property instanceof DateTimeInterface) {
                    return $arg ? $property->format($arg) : $property->format($object->getDateFormat());
                }

                return $property;
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
