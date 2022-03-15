<?php

namespace Orbit\Tests;

use Illuminate\Support\Facades\DB;
use ReflectionClass;

class CacheCommandTest extends TestCase
{
    public function test_it_caches_found_models()
    {
        $this->app->useAppPath(__DIR__.'/Fixtures/Cache');
        $this->setAppNamespace('Orbit\\Tests\\Fixtures\\Cache');

        $this->artisan('orbit:cache')
            ->expectsOutput("Cached the following Orbit models:")
            ->expectsOutput("• \Orbit\Tests\Fixtures\Cache\CachePost");
        ;

        $this->assertCount(1, DB::connection('orbit')->table('cache_posts')->get());
    }

    public function test_it_caches_no_models()
    {
        $this->app->useAppPath(__DIR__.'/Fixtures/Cache');
        $this->setAppNamespace('Foo\\');

        $this->artisan('orbit:cache')
            ->expectsOutput('Could not find any Orbit models.');
    }

    private function setAppNamespace(string $namespace): void
    {
        $reflection = new ReflectionClass($this->app);
        $reflectionNamespace = $reflection->getProperty('namespace');
        $reflectionNamespace->setAccessible(true);
        $reflectionNamespace->setValue($this->app, $namespace);
        $reflectionNamespace->setAccessible(false);
    }
}
