<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;
use Orbit\OrbitManager;

/**
 * @method static \Orbit\Contracts\Driver driver(string|null $driver)
 * @method static \Orbit\OrbitManager extend(string $driver, \Closure $callback)
 * @method static array getDrivers()
 * @method static string getDefaultDriver()
 * @method static string getDatabasePath()
 * @method static string getMetaDatabasePath()
 * @method static string getContentPath()
 * @method static \Orbit\OrbitManager test()
 * @method static bool isTesting()
 *
 * @see \Orbit\OrbitManager
 */
class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return OrbitManager::class;
    }
}
