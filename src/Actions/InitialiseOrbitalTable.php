<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Contracts\Orbit;
use Orbit\Support\ConfigureBlueprintFromModel;
use ReflectionClass;

class InitialiseOrbitalTable
{
    public function shouldInitialise(Orbit&Model $model): bool
    {
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        $modelFile = (new ReflectionClass($model))->getFileName();
        $modelFileMTime = filemtime($modelFile);
        $databaseMTime = filemtime(config('orbit.paths.database'));

        return ($modelFileMTime > $databaseMTime) || ! $schemaBuilder->hasTable($model->getTable());
    }

    public function migrate(Orbit&Model $model, Driver $driver): void
    {
        $table = $model->getTable();
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        if ($schemaBuilder->hasTable($table)) {
            $schemaBuilder->drop($table);
        }

        $schemaBuilder->create($table, static function (Blueprint $table) use ($model, $driver) {
            ConfigureBlueprintFromModel::configure($model, $table);

            if ($driver instanceof ModifiesSchema) {
                $driver->schema($table);
            }
        });
    }
}
