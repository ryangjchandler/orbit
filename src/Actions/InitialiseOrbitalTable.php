<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Orbit;
use Orbit\Support\ModelUsesSoftDeletes;

class InitialiseOrbitalTable
{
    public function hasTable(Orbit&Model $model): bool
    {
        $schemaBuilder = $model->resolveConnection()->getSchemaBuilder();

        return $schemaBuilder->hasTable($model->getTable());
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

            $model->schema($blueprint);

            if ($model->usesTimestamps()) {
                $blueprint->timestamps();
            }

            if (ModelUsesSoftDeletes::check($model)) {
                $blueprint->softDeletes();
            }
        });
    }
}
