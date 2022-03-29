<?php

namespace Orbit\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orbit\OrbitServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            OrbitServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.orbit.database', ':memory:');
    }
}
