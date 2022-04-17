<?php

namespace Orbit\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Observers\OrbitalObserver;
use Orbit\OrbitOptions;
use Orbit\Support;
use Symfony\Component\Finder\Finder;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Orbital
{
    use Internal\HandlesOrbitConnection;

    abstract public static function schema(Blueprint $table): void;

    abstract public static function getOrbitOptions(): OrbitOptions;

    public static function bootOrbital(): void
    {
        $options = static::getOrbitOptions();

        if (Support::modelNeedsMigration(static::class)) {
            static::migrate($options);
        }

        static::observe(OrbitalObserver::class);
    }

    /** @internal */
    protected static function migrate(OrbitOptions $options): void
    {
        $model = new static();
        $schema = static::resolveConnection()->getSchemaBuilder();
        $table = $model->getTable();
        $driver = $options->getDriver();

        // 1. We first drop the existing table.
        $schema->dropIfExists($table);

        // 2. We then need to migrate the new table to ensure it's all up-to-date.
        $schema->create($table, function (Blueprint $table) use ($driver, $model) {
            static::schema($table);

            if ($driver instanceof ModifiesSchema) {
                $driver->schema($table);
            }

            Support::callTraitMethods($model, 'schema', ['table' => $table]);

            if ($model->usesTimestamps()) {
                $table->timestamps();
            }
        });

        // 3. Now that the table exists, we also need to make sure the directories
        //    for the model exist.
        $source = $options->getSource($model);

        // 3b. Laravel's excellent Filesystem API can make this simple.
        File::ensureDirectoryExists($source);

        // 4. Now that know all of the correct things are in place, we can start seeding data.
        //    The first step is finding all files in the source directory.
        $files = Finder::create()
            ->in($source)
            ->files()
            ->name("*.{$driver->extension()}")
            ->sortByModifiedTime();

        // 4a. For each of the files in that directory, we need to insert a record into the
        //     the SQLite database cache.
        foreach ($files as $file) {
            $path = $file->getPathname();

            if (! Support::fileNeedsToBeSeeded($path, static::class)) {
                continue;
            }

            $record = new static($driver->fromFile($file->getPathname()));

            // 4b. We want to updateOrCreate so that we don't need to wipe out
            //     the entire cache. This should be a performance boost on larger project.
            static::query()->updateOrCreate([
                $record->getKeyName() => $record->getKey(),
            ], Arr::except($record->getAttributes(), $record->getKeyName()));
        }
    }
}
