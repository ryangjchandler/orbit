<?php

use Orbit\Tests\DefaultColumnValues\DefaultColumnValues;
use Illuminate\Support\Facades\File;

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

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__ . '/content/');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/content/');
});
