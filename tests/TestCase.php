<?php

namespace Orbit\Tests;

use Orbit\OrbitServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OrbitServiceProvider::class,
        ];
    }
}
