<?php

use function PHPUnit\Framework\assertDispatched;
use function PHPUnit\Framework\assertFileExists;
use Illuminate\Support\Facades\Event;
use Orbit\Events\OrbitSeeded;
use Orbit\Tests\Manual\Manual as Model;

test('manual > creating a file manually adds it to the cache', function () {
    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    assertFileExists(__DIR__ . '/content/1.md');

    expect(Model::first())
        ->toBeInstanceOf(Model::class)
        ->id->toBe(1)
        ->title->toBe('Foo');
});

test('manual > creating a file and updating that file updates the cache', function () {
    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    assertFileExists(__DIR__ . '/content/1.md');

    expect(Model::first())
        ->toBeInstanceOf(Model::class)
        ->id->toBe(1)
        ->title->toBe('Foo');

    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foobar
    ---

    Hello, world!
    md);

    Model::clearBootedModels();

    expect(Model::first())
        ->toBeInstanceOf(Model::class)
        ->id->toBe(1)
        ->title->toBe('Foobar');
});

test('manual > creating a file dispatches orbital seeded event', function () {
    Event::fake();

    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    Model::first();

    Event::assertDispatched(OrbitSeeded::class);
});

afterEach(function () {
    Model::all()->each->delete();
});
