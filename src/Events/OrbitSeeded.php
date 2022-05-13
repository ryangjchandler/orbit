<?php

namespace Orbit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrbitSeeded
{
    use Dispatchable;

    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }
}
