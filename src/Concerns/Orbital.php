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
use ReflectionMethod;
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
            static::seedData($options, force: true);
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

        // Boot any associated pivot relationships of this model
        collect((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionMethod $method) {
                return $method->getReturnType() == 'Illuminate\Database\Eloquent\Relations\BelongsToMany';
            })
            ->each(function (ReflectionMethod $method) use ($model) {
                $pivotClass = $method->invoke($model)->getPivotClass();
                // Trigger boot
                new $pivotClass();
            });
    }

    protected static function seedData(OrbitOptions $options, bool $force = false): void
    {
        $model = new static();

        $model::handleUpdatedOrCreatedFiles($model, $options, $force);

        $model::handleDeletedFiles($model, $options);
    }

    protected static function handleUpdatedOrCreatedFiles($model, $options, $force)
    {
        $model = new static();
        $driver = $options->getDriver();
        $source = $options->getSource($model);

        // Application tests fail without this additional existence check.
        File::ensureDirectoryExists($source);

        $files = Finder::create()
            ->in($source)
            ->files()
            ->name("*.{$driver->extension()}");

        if (!$force) {
            // We determine if the file should be put into the database by comparing it's modified date with the SQLite / Model file.
            // If the file is newer than the sqlite database file, it probably has new or changed data so should be added/updated in the database.
            $files = $files->date('> ' . Carbon::createFromTimestamp(filemtime(Orbit::getCachePath()))->format('Y-m-d H:i:s'));
        }

        $recordsToUpsert = [];
        $columnExplodeString = '_' . Str::random(12) . '_';

        $schema = static::resolveConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        foreach ($files as $file) {

            $path = $file->getPathname();
            $record = new static($driver->fromFile($path));

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
        if (collect($recordsToUpsert)->count()) {
            collect($recordsToUpsert)->each(function ($chunkedRecords, $schemaString) use ($model, $columnExplodeString) {
                collect($chunkedRecords)->chunk(200)->each(function ($chunkedRecordsToUpsert) use ($model, $schemaString, $columnExplodeString) {
                    $model::upsert(
                        values: $chunkedRecordsToUpsert->toArray(),
                        uniqueBy: [$model->getKeyName()],
                        update: Str::of($schemaString)->explode($columnExplodeString)->toArray()
                    );
                });
            });
        }

        // Get the recently created models
        $recentlyInsertedModels = $model::where('orbit_recently_inserted', 1);

        // Fire OrbitSeeded event on each dirty model
        $recentlyInsertedModels->get()->each(fn ($recentlyInsertedModel) => OrbitSeeded::dispatch($recentlyInsertedModel));

        // Run mass update to unset recently inserted flag
        // No need for upsert here as Laravel supports mass updates in core
        $recentlyInsertedModels->update(['orbit_recently_inserted' => 0]);
    }

    protected static function handleDeletedFiles($model, $options)
    {
        if (!config('orbit.manual_mode')) {
            return;
        }

        $driver = $options->getDriver();
        $source = $options->getSource($model);

        $files = Finder::create()
            ->in($source)
            ->files()
            ->name("*.{$driver->extension()}");

        $fileData = [];

        foreach ($files as $file) {
            $fileData[] = $file->getPathname();
        }

        $filepathsFromDB = $model::select('orbit_file_path')->get()->flatten(1)->pluck('orbit_file_path');

        $filepathsFromFiles = collect($fileData);

        $filepathsFromDB->diff($filepathsFromFiles)->each(function (?string $filepath) use ($model) {
            $model::where('orbit_file_path', $filepath)->delete();
        });
    }
}
