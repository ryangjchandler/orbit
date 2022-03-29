<?php

use Orbit\Tests\Simple\Simple;

use function PHPUnit\Framework\assertFileExists;

test('simple > creating a model creates a file', function () {
    Simple::create([
        'title' => 'Orbit!',
    ]);

    assertFileExists(__DIR__ . '/content/1.md');
});

afterEach(function () {
    Simple::all()->each->delete();
});
