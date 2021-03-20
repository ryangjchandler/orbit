<?php

namespace Orbit;

use Illuminate\Support\Manager;

class OrbitManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('orbit.default');
    }

    public function getDatabasePath()
    {
        return config('orbit.paths.cache') . DIRECTORY_SEPARATOR . 'orbit.sqlite';
    }
}
