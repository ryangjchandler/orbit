<?php

use Illuminate\Support\Facades\Event;
use Orbit\Tests\Manual\Manual as Model;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertDispatched;

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

// test('manual > creating a file dispatches model events', function () {

//     file_put_contents(__DIR__ . '/content/1.md', <<<'md'
//     ---
//     id: 1
//     title: Foo
//     ---

//     Hello, world!
//     md);

//     Event::fake();

//     Model::first();

//     Event::assertDispatched("eloquent.created: " . Model::class);
// });

afterEach(function () {
    Model::all()->each->delete();
});
