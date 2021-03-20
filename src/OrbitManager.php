<?php

namespace Orbit;

use Illuminate\Support\Manager;

class OrbitManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('orbit.default');
    }
}
