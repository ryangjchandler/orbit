<?php

namespace Orbit\Observers;

use Illuminate\Database\Eloquent\Model;

class SoftDeletesObserver
{
    public function restored(Model $model)
    {
        (new OrbitalObserver())->updated($model);
    }

    public function deleted(Model $model)
    {
        (new OrbitalObserver())->updated($model);
    }

    public function forceDeleted(Model $model)
    {
        (new OrbitalObserver())->deleted($model);
    }
}
