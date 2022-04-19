<?php

use Illuminate\Support\Facades\File;
use Orbit\Models\Meta;
use Orbit\Tests\CustomFilepath\CustomFilepath;
use Orbit\Tests\CustomFilepath\StaticCustomFilepath;

use function PHPUnit\Framework\assertFileDoesNotExist;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertFileNotExists;

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

    $record = CustomFilepath::first();

    expect($record)
        ->title->toBe('bar-baz');

    expect($record->orbitMeta)
        ->file_path_read_from->toBe('2022-01-01/bar-baz.md');
});

test('custom filepath > correctly removes outdated files when filepath changes', function () {
    $record = CustomFilepath::create([
        'title' => 'barbar',
        'published_at' => now(),
    ]);

    assertFileExists(__DIR__ . '/content/' . $record->published_at->format('Y-m-d') . '/barbar.md');

    $record->update([
        'title' => 'booboo',
    ]);

    assertFileExists(__DIR__ . '/content/' . $record->published_at->format('Y-m-d') . '/booboo.md');
    assertFileDoesNotExist(__DIR__ . '/content/' . $record->published_at->format('Y-m-d') . '/barbar.md');
});

test('custom filepath > it can use static data in filepath', function () {
    StaticCustomFilepath::create([
        'title' => 'foobar'
    ]);

    assertFileExists(__DIR__ . '/static-content/static-folder/foobar.md');
});

test('custom filepath > it can seed data from file with static filepath', function () {
    if (! is_dir($path = __DIR__ . '/static-content/static-folder')) {
        File::ensureDirectoryExists($path);
    }

    file_put_contents($path . '/bahbah.md', <<<'md'
    ---
    id: 1
    title: bahbah
    ---
    md);

    $record = StaticCustomFilepath::first();

    expect($record)
        ->title->toBe('bahbah');

    $record->update([
        'title' => 'blahblah'
    ]);

    assertFileExists(__DIR__ . '/static-content/static-folder/blahblah.md');
    assertFileDoesNotExist(__DIR__ . '/static-content/static-folder/bahbah.md');
});

afterEach(function () {
    CustomFilepath::all()->each->delete();
    StaticCustomFilepath::all()->each->delete();
});
