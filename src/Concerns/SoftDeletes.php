<?php

namespace Orbit\Concerns;

use Orbit\Facades\Orbit;
use Orbit\Events\OrbitalDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;

trait SoftDeletes
{
    use EloquentSoftDeletes;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::deleted(function (Model $model) {
            $status = Orbit::driver(static::getOrbitalDriver())->save(
                $model, static::getOrbitalPath()
            );

            OrbitalDeleted::dispatch($model);

            return $status;
        });

        static::forceDeleted(function (Model $model) {
            if ($model->callTraitMethod('shouldDelete', $model) === false) {
                return;
            }

            $status = Orbit::driver(static::getOrbitalDriver())->delete(
                $model,
                static::getOrbitalPath()
            );

            OrbitalDeleted::dispatch($model);

            return $status;
        });
    }

    public function schemaSoftDeletes(Blueprint $table)
    {
        $hasSoftDeletesColumn = collect($table->getColumns())->contains(function (ColumnDefinition $column) {
            return $column->get('name') === $this->getDeletedAtColumn();
        });

        if ($hasSoftDeletesColumn) {
            return;
        }

        $table->softDeletes($this->getDeletedAtColumn());
    }

    public function shouldDeleteSoftDeletes()
    {
        return $this->isForceDeleting();
    }
}