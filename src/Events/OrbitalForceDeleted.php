<?php

namespace Orbit\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class OrbitalForceDeleted
{
    use Dispatchable;

    public $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
