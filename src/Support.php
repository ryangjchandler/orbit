<?php

namespace Orbit;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Orbit\Facades\Orbit;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

/** @internal */
final class Support
{
    public static function callTraitMethods(Model $object, string $prefix, array $args = []): void
    {
        foreach (class_uses_recursive($object) as $trait) {
            $method = $prefix . class_basename($trait);

            if (! method_exists($object, $method)) {
                continue;
            }

            $object->{$method}(...$args);
        }
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

        $table = (new $modelClass)->getTable();

        if (! $modelClass::resolveConnection()->getSchemaBuilder()->hasTable($table)) {
            return true;
        }

        return false;
    }
}
