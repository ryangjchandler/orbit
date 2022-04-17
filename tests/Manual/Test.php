<?php

use Orbit\Tests\Manual;

use function PHPUnit\Framework\assertFileExists;
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

afterEach(function () {
    Model::all()->each->delete();
});
