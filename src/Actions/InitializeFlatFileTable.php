<?php

namespace RyanChandler\FlatFile\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\ModifiesSchema;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;
use RyanChandler\FlatFile\Support\ConfigureBlueprintFromModel;
use ReflectionClass;

class InitializeFlatFileTable
{
    public function shouldInitialise(InteractsWithFlatFiles&Model $model): bool
    {
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        $modelFile = (new ReflectionClass($model))->getFileName();
        $modelFileMTime = filemtime($modelFile);
        $databaseMTime = filemtime(config('flat-file.paths.database'));

        return ($modelFileMTime > $databaseMTime) || ! $schemaBuilder->hasTable($model->getTable());
    }

    public function migrate(InteractsWithFlatFiles&Model $model, Driver $driver): void
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
