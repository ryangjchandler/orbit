<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;
use Orbit\Orbit as OrbitManager;

/**
 * @method static string getCachePath()
 * @method static string getContentPath()
 *
 * @see \Orbit\Orbit
 */
class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OrbitManager::class;
    }
}
