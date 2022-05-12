<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Orbit\Models\Meta;
use Orbit\Tests\Seeder\TestModel;

use function PHPUnit\Framework\assertDispatched;
use function PHPUnit\Framework\assertFileExists;
use function PHPUnit\Framework\assertEquals;
/*
test('seeder > it always updates the meta file path for the correct models',  function () {
    // The meta file path is updated using the key of each recently inserted model.

    // Create a few records
    $filenames = range(1, 10);
    shuffle($filenames);

    foreach ($filenames as $filename) {
        TestModel::create([
            'title' => 'Test record #' . $filename,
        ]);

        unset($filenames[$filename]);
    }

    foreach ($filenames as $filename) {
        assertFileExists(__DIR__ . '/content/' . $filename . '.md');
    }

    // Clearing will prevent orbit from being able to delete the created files.
    Artisan::call('orbit:clear');

    // The model is already booted, so we will manually clear for testing
    TestModel::clearBootedModels();

    // Manually boot again
    new TestModel();

    Meta::all()->each(function (Meta $meta) {
        assertEquals($meta->orbital_key, Str::of($meta->file_path_read_from)->remove('.md'));
    });
}); */
/*
test('seeder > it triggers eloquent events when upsert seeding', function () {
    TestModel::create([
        'title' => 'Orbit!',
    ]);

    Event::fake();

    Event::assertDispatched("eloquent.created: " . TestModel::class);
});

test('seeder > it casts attributes when upsert seeding', function () {
    //
});

test('seeder > it custom casts attributes when upsert seeding', function () {
    //
});

afterEach(function () {
    TestModel::all()->each->delete();
});
 */
