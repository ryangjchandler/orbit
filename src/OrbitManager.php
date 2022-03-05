<?php

namespace Orbit;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Manager;

class OrbitManager extends Manager
{
    protected $testing = false;

    public function test()
    {
        $this->testing = true;

        return $this;
    }

    public function isTesting()
    {
        return $this->testing === true || App::environment('testing');
    }

    public function getDefaultDriver()
    {
        return $this->config->get('orbit.default');
    }

    public function getDatabasePath()
    {
        if ($this->isTesting()) {
            return ':memory:';
        }

        return config('orbit.paths.cache') . DIRECTORY_SEPARATOR . 'orbit.sqlite';
    }

    public function getMetaDatabasePath()
    {
        return storage_path('framework/cache/orbit/orbit_meta.sqlite');
    }

    public function getContentPath()
    {
        return config('orbit.paths.content');
    }
}
