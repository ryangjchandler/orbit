<?php

use Orbit\Tests\SoftDeletes\SoftDeletesModel;
use Illuminate\Support\Facades\File;

use function PHPUnit\Framework\assertFileExists;

test('soft deletes > update the file correctly', function () {
    $model = SoftDeletesModel::create([
        'title' => 'foobar',
    ]);

    $path = __DIR__ . "/content/{$model->getKey()}.md";

    assertFileExists($path);

    $model->delete();

    assertFileExists($path);
    assertFileContains($path, 'deleted_at: ' . $model->deleted_at->toIso8601String());
});

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__ . '/content/');
});

afterEach(function () {
    File::deleteDirectory(__DIR__ . '/content/');
});
