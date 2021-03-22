<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

trait SoftDeletes
{
    use EloquentSoftDeletes;

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
}
