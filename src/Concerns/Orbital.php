<?php

namespace Orbit\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Events\OrbitSeeded;
use Orbit\Facades\Orbit;
use Orbit\Observers\OrbitalObserver;
use Orbit\OrbitOptions;
use Orbit\Support;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Orbital
{
    use Internal\HandlesOrbitConnection;

    public static function bootOrbital(): void
    {
        $options = static::getOrbitOptions();

        if (!$options->isEnabled()) {
            return;
        }

        if (!File::exists(Orbit::getCachePath())) {
            File::put(Orbit::getCachePath(), '');
        }

        if (Support::modelNeedsMigration(static::class)) {
            static::migrate($options);
            static::seedData($options, force:true);
        } else {
            static::seedData($options);
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

            $table->string('orbit_file_path')->nullable()->unique();
            $table->boolean('orbit_recently_inserted')->default(0);

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
    }

    protected static function seedData(OrbitOptions $options, bool $force = false): void
    {
        $model = new static();
        $driver = $options->getDriver();
        $source = $options->getSource($model);

        $orbitCacheFile = Orbit::getCachePath();
        $modelFile = (new ReflectionClass(static::class))->getFileName();
        $oldestFile = filemtime($modelFile) > filemtime($orbitCacheFile) ? $orbitCacheFile : $modelFile;

        $files = Finder::create()
            ->in($source)
            ->files()
            ->name("*.{$driver->extension()}");

        if (!$force) {
            $files = $files->date('> ' . Carbon::createFromTimestamp(filemtime($oldestFile))->format('Y-m-d H:i:s'));
        }

        $recordsToUpsert = [];
        $pathsToUpsert = [];
        $columnExplodeString = '_' . Str::random(12) . '_';

        if (!count($files)) {
            return;
        }

        foreach ($files as $file) {

            $path = $file->getPathname();
            $record = new static($driver->fromFile($path));
            $schema = static::resolveConnection()->getSchemaBuilder()->getColumnListing($record->getTable());

            $attributesForInsert = collect($record->getAttributesForInsert())
                ->only($schema)
                ->put('orbit_file_path', $path)
                ->put('orbit_recently_inserted', 1)
                ->all();

            // ❕ You have to add files to seperate arrays using their attributes as a key, incase of attributes missing.
            // If not, you get `General error: 1 all VALUES must have the same number of terms.`
            $attributeKeysPresent = collect($attributesForInsert)->keys()->implode($columnExplodeString);

            // Build array of records for bulk upsert later.
            $recordsToUpsert[$attributeKeysPresent][] = $attributesForInsert;
        }

        // Upsert the records in bulk
        collect($recordsToUpsert)->each(function ($chunkedRecords, $schemaString) use ($model, $columnExplodeString) {
            collect($chunkedRecords)->chunk(200)->each(function ($chunkedRecordsToUpsert) use ($model, $schemaString, $columnExplodeString) {
                $model::upsert(
                    values:$chunkedRecordsToUpsert->toArray(),
                    uniqueBy:[$model->getKeyName()],
                    update:Str::of($schemaString)->explode($columnExplodeString)->toArray()
                );
            });
        });

        // Get the recently created models
        $recentlyInsertedModels = $model::where('orbit_recently_inserted', 1);

        // Fire OrbitSeeded event on each dirty model
        $recentlyInsertedModels->get()->each(fn($recentlyInsertedModel) => OrbitSeeded::dispatch($recentlyInsertedModel));

        // Run mass update to unset recently inserted flag
        // No need for upsert here as Laravel supports mass updates in core
        $recentlyInsertedModels->update(['orbit_recently_inserted' => 0]);
    }
}
