<?php

namespace Orbit;

use Closure;
use Illuminate\Support\Manager;
use Illuminate\Support\Facades\App;

class OrbitManager extends Manager
{
    protected $testing = false;

    protected Closure $resolveGitName;

    protected Closure $resolveGitEmail;

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

    public function getGitName()
    {
        if ($this->resolveGitName) {
            return value($this->resolveGitName);
        }

        return config('orbit.git.name');
    }

    public function getGitEmail()
    {
        if ($this->resolveGitEmail) {
            return value($this->resolveGitEmail);
        }

        return config('orbit.git.email');
    }

    public function getGitRoot()
    {
        return config('orbit.git.root');
    }

    public function getGitBinary()
    {
        return config('orbit.git.binary');
    }
}
