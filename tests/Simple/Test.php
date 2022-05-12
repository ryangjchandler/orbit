<?php

use Illuminate\Support\Facades\Event;
use Orbit\Models\Meta;
use Orbit\Tests\Simple\Simple;

use function PHPUnit\Framework\assertDispatched;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertSame;

test('simple > creating a model creates a file', function () {
    Simple::create([
        'title' => 'Orbit!',
    ]);

    assertFileExists(__DIR__ . '/content/1.md');
});

test('simple > deleting a model deletes the file', function () {
    $s = Simple::create([
        'title' => 'Orbit!',
    ]);

    assertFileExists(__DIR__ . '/content/1.md');

    $s->delete();

    assertFileDoesNotExist(__DIR__ . '/content/1.md');
});

test('simple > updating a model updates the file', function () {
    $s = Simple::create([
        'title' => 'Orbit!',
    ]);

    assertFileContains(__DIR__ . '/content/1.md', $s->title);

    $s->update([
        'title' => 'Another orbit!',
    ]);

    assertFileContains(__DIR__ . '/content/1.md', $s->title);
});

test('simple > default column values are persisted to disk', function () {
    Simple::create([
        'title' => 'Foobar',
    ]);

    assertFileContains(__DIR__ . '/content/1.md', 'published: false');
});


test('simple > creating a file dispatches model events', function () {

    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    Event::fake();

    Simple::first();

    Event::assertDispatched("eloquent.created: " . Simple::class);
});

afterEach(function () {
    Simple::all()->each->delete();
});
