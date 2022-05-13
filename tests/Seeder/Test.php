<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Orbit\Events\OrbitSeeded;
use Orbit\Tests\Seeder\TestModel;

use function PHPUnit\Framework\assertDispatched;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;

test('seeder > it triggers a eloquent event when using create method', function () {
    Event::fake();

    TestModel::create([
        'title' => 'Orbit!',
    ]);

    // Eloquent event happens when using `create()`
    Event::assertDispatched('eloquent.created: ' . TestModel::class);
});

test('seeder > it triggers a custom event when manually creating files', function () {
    Event::fake();

    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    assertFileExists(__DIR__ . '/content/1.md');

    expect(TestModel::first())
        ->toBeInstanceOf(TestModel::class)
        ->id->toBe(1)
        ->title->toBe('Foo');

    // Eloquent event happens when using `create()`
    Event::assertDispatched(OrbitSeeded::class);
});


test('seeder > it sets dirty states correctly during seeding', function () {
    $m = TestModel::create([
        'title' => 'Orbit!',
    ]);

    assertEquals($m->orbit_recently_inserted, 0);
});

test('seeder > it casts attributes when upsert seeding', function () {
    //
});

test('seeder > it custom casts attributes when upsert seeding', function () {
    //
});

afterEach(function () {
    TestModel::all()->each->delete();
});
