<?php

use Illuminate\Support\Facades\File;
use Orbit\Tests\CustomFilepath\CustomFilepath;

use function PHPUnit\Framework\assertFileExists;

test('custom filepath > creates a file with a custom filepath', function () {
    $record = CustomFilepath::create([
        'title' => 'foo-bar',
        'published_at' => now(),
    ]);

    assertFileExists(__DIR__ . '/content/' . $record->published_at->format('Y-m-d') . '/foo-bar.md');
});

test('custom filepath > reads files with custom filepath', function () {
    if (! is_dir($path = __DIR__ . '/content/2022-01-01')) {
        File::ensureDirectoryExists($path);
    }

    file_put_contents($path . '/bar-baz.md', <<<'md'
    ---
    id: 1
    title: bar-baz
    published_at: 2022-01-01T00:00:00+00:00
    ---
    md);

    expect(CustomFilepath::first())
        ->title->toBe('bar-baz');
});

afterEach(function () {
    CustomFilepath::all()->each->delete();
});
