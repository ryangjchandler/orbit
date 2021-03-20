<?php

namespace Orbit\Facades;

use Illuminate\Support\Facades\Facade;

class Orbit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'orbit';
    }
}
