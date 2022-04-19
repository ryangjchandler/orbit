<?php


use Orbit\Tests\SoftDeletes\SoftDeletesModel;

use function PHPUnit\Framework\assertFileExists;

test('soft deletes > update the file correctly', function () {
    $model = SoftDeletesModel::create([
        'title' => 'foobar',
    ]);

    assertFileExists(__DIR__ . '/content/1.md');

    $model->delete();

    assertFileExists(__DIR__ . '/content/1.md');
    assertFileContains(__DIR__ . '/content/1.md', 'deleted_at: ' . $model->deleted_at->toIso8601String());
});

afterEach(function () {
    SoftDeletesModel::all()->each->forceDelete();
});
