<?php

namespace RyanChandler\FlatFile\Facades;

use Illuminate\Support\Facades\Facade;

class FlatFile extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return \RyanChandler\FlatFile\FlatFile::class;
    }
}
