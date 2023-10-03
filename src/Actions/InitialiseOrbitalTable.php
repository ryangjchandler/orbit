<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Orbit;
use Orbit\Support\ConfigureBlueprintFromModel;
use Orbit\Support\ModelUsesSoftDeletes;
use ReflectionClass;

class InitialiseOrbitalTable
{
    public function shouldInitialise(Orbit&Model $model): bool
    {
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        $modelFile = (new ReflectionClass($model))->getFileName();
        $modelFileMTime = filemtime($modelFile);
        $databaseMTime = filemtime(config('orbit.paths.database'));

        return ($modelFileMTime > $databaseMTime) || !$schemaBuilder->hasTable($model->getTable());
    }

    public function migrate(Orbit&Model $model): void
    {
        $table = $model->getTable();
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        if ($schemaBuilder->hasTable($table)) {
            $schemaBuilder->drop($table);
        }

        $blueprint = null;

        $schemaBuilder->create($table, static function (Blueprint $table) use (&$blueprint, $model) {
            $blueprint = $table;

            ConfigureBlueprintFromModel::configure($model, $blueprint);
        });
    }
}
