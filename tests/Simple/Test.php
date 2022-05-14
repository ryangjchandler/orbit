<?php

use function PHPUnit\Framework\assertDispatched;
use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Orbit\Events\OrbitSeeded;
use Orbit\Tests\Simple\Simple;

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

test('simple > creating a file dispatches orbital seeded event', function () {
    Event::fake();

    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    title: Foo
    ---

    Hello, world!
    md);

    Simple::first();

    Event::assertDispatched(OrbitSeeded::class);
});

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__ . '/content');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/content');
});
