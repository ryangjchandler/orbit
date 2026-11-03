<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Orbit\Index;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory(config('orbit.paths.content'));
        $this->app->make(Index::class)->clear();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(config('orbit.paths.content'));

        parent::tearDown();
    }
}
