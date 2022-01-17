<?php

namespace Orbit\Tests;

use Orbit\Facades\Orbit;
use Orbit\OrbitServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OrbitServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('orbit.paths.content', __DIR__.'/content');

        Orbit::test();
    }
}
