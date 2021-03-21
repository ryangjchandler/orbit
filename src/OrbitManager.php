<?php

namespace Orbit;

use Closure;
use Illuminate\Support\Manager;

class OrbitManager extends Manager
{
    protected Closure $resolveGitName;

    protected Closure $resolveGitEmail;

    public function getDefaultDriver()
    {
        return $this->config->get('orbit.default');
    }

    public function getDatabasePath()
    {
        return config('orbit.paths.cache') . DIRECTORY_SEPARATOR . 'orbit.sqlite';
    }

    public function resolveGitNameUsing(Closure $callback)
    {
        $this->resolveGitName = $callback;

        return $this;
    }

    public function resolveGitEmailUsing(Closure $callback)
    {
        $this->resolveGitEmail = $callback;

        return $this;
    }
}
