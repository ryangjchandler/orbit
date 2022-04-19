<?php

use Illuminate\Database\Eloquent\Model;
use Orbit\Tests\DefaultColumnValues\DefaultColumnValues;

use function PHPUnit\Framework\assertFileExists;

test('default column values > default values get persisted to disk', function () {
    DefaultColumnValues::create();

    assertFileExists(__DIR__ . '/content/1.md');
    assertFileContains(__DIR__ . '/content/1.md', 'foo: bar');
});

test('default column values > default values missing from file get added', function () {
    file_put_contents(__DIR__ . '/content/1.md', <<<'md'
    ---
    id: 1
    ---
    md);

    expect(DefaultColumnValues::first())
        ->foo->toBe('bar');
});

afterEach(function () {
    DefaultColumnValues::all()->each->delete();
});
