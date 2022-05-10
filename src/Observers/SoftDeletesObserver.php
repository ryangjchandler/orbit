<?php

namespace Orbit\Observers;

use Illuminate\Database\Eloquent\Model;
use Orbit\Contracts\IsOrbital;

class SoftDeletesObserver
{
    public function restored(Model & IsOrbital $model)
    {
        (new OrbitalObserver())->updated($model);
    }

    public function deleted(Model & IsOrbital $model)
    {
        (new OrbitalObserver())->updated($model);
    }

    public function forceDeleted(Model & IsOrbital $model)
    {
        (new OrbitalObserver())->deleted($model);
    }
}
