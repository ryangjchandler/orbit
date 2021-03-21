<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Orbit\Contracts\Driver driver(string|null $driver)
 * @method static \Orbit\OrbitManager extend(string $driver, \Closure $callback)
 * @method static \Orbit\OrbitManager resolveGitNameUsing(\Closure $callback)
 * @method static \Orbit\OrbitManager resolveGitEmailUsing(\Closure $callback)
 * @method static array getDrivers()
 * @method static string getDefaultDriver()
 * @method static string getDatabasePath()
 *
 * @see \Orbit\OrbitManager
 */
class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'orbit';
    }
}
