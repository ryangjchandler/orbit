<?php

namespace Orbit;

use Illuminate\Support\Facades\App;
use Orbit\Facades\Orbit;
use ReflectionClass;

/** @internal */
final class Support
{
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
