<?php

use Illuminate\Support\Facades\Artisan;
use Orbit\Index;

it('provides default paths and model directories', function () {
    expect(config('orbit.paths.database'))->toBe(storage_path('framework/cache/orbit/database.sqlite'))
        ->and(config('orbit.paths.content'))->toBe(base_path('content'))
        ->and(config('orbit.models'))->toBe([app_path('Models')]);
});

it('registers an orbit SQLite connection for the cache database', function () {
    expect(config('database.connections.orbit'))->toBe([
        'driver' => 'sqlite',
        'database' => config('orbit.paths.database'),
        'foreign_key_constraints' => false,
        'busy_timeout' => 5000,
    ]);
});

it('shares one index per request', function () {
    $index = app(Index::class);

    expect(app(Index::class))->toBe($index);

    nextRequest();

    expect(app(Index::class))->not->toBe($index);
});

it('registers the orbit commands', function () {
    expect(Artisan::all())->toHaveKeys(['orbit:install', 'orbit:cache', 'orbit:clear']);
});

it('creates the content directory when installed', function () {
    expect(content_path())->not->toBeDirectory();

    $this->artisan('orbit:install')
        ->expectsConfirmation('Would you like to star our repo on GitHub?', 'no')
        ->assertSuccessful();

    expect(content_path())->toBeDirectory();
});
