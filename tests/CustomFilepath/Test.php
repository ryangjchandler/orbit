<?php

use Orbit\Tests\CustomFilepath\CustomFilepath;

use function PHPUnit\Framework\assertFileExists;

test('custom filepath > it can create a file with a custom filepath', function () {
    $record = CustomFilepath::create([
        'title' => 'foo-bar',
        'published_at' => now(),
    ]);

    assertFileExists(__DIR__ . '/content/' . $record->published_at->format('Y-m-d') . '/foo-bar.md');
});

afterEach(function () {
    CustomFilepath::all()->each->delete();
});
