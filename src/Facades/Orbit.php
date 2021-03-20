<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Orbit\OrbitManager
 */
class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'orbit';
    }
}
