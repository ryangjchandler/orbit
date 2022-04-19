<?php

namespace Orbit\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Schema\Blueprint;
use Orbit\Observers\SoftDeletesObserver;

trait SoftDeletes
{
    use EloquentSoftDeletes;

    public static function bootSoftDeletes()
    {
        static::addGlobalScope(new SoftDeletingScope());
        static::observe(SoftDeletesObserver::class);
    }

    public function schemaSoftDeletes(Blueprint $table): void
    {
        $table->softDeletes($this->getDeletedAtColumn());
    }
}
