<?php

namespace RyanChandler\FlatFile;

use Illuminate\Config\Repository;

class FlatFile
{
    /**
     * Create a new instance.
     */
    public function __construct(protected Repository $config)
    {
        //
    }
}
