<?php

namespace Orbit\Tests;

use Orbit\Drivers\Markdown;
use Orbit\Drivers\Json;
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

    public function test_it_can_return_a_custom_git_name()
    {
        Orbit::resolveGitNameUsing(fn () => 'Ryan');

        $this->assertEquals('Ryan', Orbit::getGitName());
    }

    public function test_it_can_return_a_custom_git_email()
    {
        Orbit::resolveGitEmailUsing(fn () => 'ryan@test.com');

        $this->assertEquals('ryan@test.com', Orbit::getGitEmail());
    }

    public function test_it_can_return_a_custom_git_root()
    {
        config(['orbit.git.root' => '/my/folder']);

        $this->assertEquals('/my/folder', Orbit::getGitRoot());
    }

    public function test_it_can_return_a_custom_git_binary()
    {
        config(['orbit.git.binary' => '/usr/local/bin/git']);

        $this->assertEquals('/usr/local/bin/git', Orbit::getGitBinary());
    }
}
