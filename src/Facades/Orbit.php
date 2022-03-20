<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;
use Orbit\Orbit as OrbitManager;

class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OrbitManager::class;
    }
}
