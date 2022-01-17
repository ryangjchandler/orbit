<?php

namespace Orbit\Tests;

use Orbit\Drivers\Json;
use Orbit\Drivers\Markdown;
use Orbit\Facades\Orbit;
use Orbit\OrbitManager;

class OrbitManagerTest extends TestCase
{
    public function test_it_can_be_accessed_via_facade()
    {
        $this->assertInstanceOf(OrbitManager::class, Orbit::getFacadeRoot());
    }

    public function test_it_can_return_md_driver()
    {
        $this->assertInstanceOf(Markdown::class, Orbit::driver('md'));
    }

    public function test_it_can_return_json_driver()
    {
        $this->assertInstanceOf(Json::class, Orbit::driver('json'));
    }

    public function test_it_can_return_default_driver()
    {
        $this->assertInstanceOf(Markdown::class, Orbit::driver());
    }

    public function test_it_can_register_custom_drivers()
    {
        $class = new class () {};

        Orbit::extend('example', fn () => $class);

        $this->assertEquals($class, Orbit::driver('example'));
    }
}
