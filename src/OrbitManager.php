<?php

namespace Orbit;

use Closure;
use Illuminate\Support\Facades\Auth;
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
