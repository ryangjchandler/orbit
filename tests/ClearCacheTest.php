<?php

namespace Orbit\Tests;

use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Event;
use Orbit\Actions\ClearCache;

class ClearCacheTest extends TestCase
{
    public function test_it_clears_orbit_cache_when_migrating_fresh()
    {
        Event::fake();
        Event::assertListening(DatabaseRefreshed::class, ClearCache::class);
    }
}
