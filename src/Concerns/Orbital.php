<?php

namespace Orbit\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Facades\Orbit;
use Orbit\Models\Meta;
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
            static::seedDataUsingUpsert($options, force: true);
        } else {
            static::seedDataUsingUpsert($options);
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
            $table->boolean('orbit_needs_meta')->default(0);

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

        // 0. Find the oldest file out of model class and the orbit cache.
        //    This allows us to limit which files are returned in the Finder iterator for performance.
        $orbitCacheFile = Orbit::getCachePath();
        $modelFile = (new ReflectionClass(static::class))->getFileName();
        $oldestFile = filemtime($modelFile) > filemtime($orbitCacheFile) ? $orbitCacheFile : $modelFile;

        // 1. Now that know all of the correct things are in place, we can start seeding data.
        //    The first step is finding all files in the source directory.
        $files = Finder::create()
            ->in($source)
            ->files()
            ->name("*.{$driver->extension()}")
            ->sortByModifiedTime();

        if (!$force) {
            $files = $files->date('> ' . Carbon::createFromTimestamp(filemtime($oldestFile))->format('Y-m-d H:i:s'));
        }

        // 1a. For each of the files in that directory, we need to insert a record into the
        //     the SQLite database cache.
        foreach ($files as $file) {
            $path = $file->getPathname();

            $record = new static($driver->fromFile($path));
            $schema = static::resolveConnection()->getSchemaBuilder()->getColumnListing($record->getTable());

            // 1b. We need to drop any values from the file that do not have a valid DB column.
            $attributes = collect($record->getAttributes())
                ->except($record->getKeyName())
                ->only($schema)
                // 1bb. This is needed to ensure all values are casted correctly before inserting.
                ->map(fn ($_, $key) => $record->{$key})
                ->all();

            // 1c. We want to updateOrCreate so that we don't need to wipe out
            //     the entire cache. This should be a performance boost on larger projects.
            $record = static::query()->updateOrCreate([
                $record->getKeyName() => $record->getKey(),
            ], $attributes);

            Meta::query()
                ->where('orbital_type', $record->getMorphClass())
                ->where('orbital_key', $record->getKey())
                ->delete();

            $record->orbitMeta()->create([
                'file_path_read_from' => ltrim(Str::after($path, $options->getSource($record)), '/'),
            ]);
        }
    }

    protected static function seedDataUsingUpsert(OrbitOptions $options, bool $force = false): void
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

        foreach ($files as $file) {

            $path = $file->getPathname();
            $record = new static($driver->fromFile($path));
            $schema = static::resolveConnection()->getSchemaBuilder()->getColumnListing($record->getTable());

            $attributesForInsert = collect($record->getAttributesForInsert())
                ->only($schema)
                ->put('orbit_file_path', $path)
                ->put('orbit_needs_meta', 1)
                ->all();

            // ❕ You have to add files to seperate arrays using their attributes as a key, incase of attributes missing.
            // If not, you get `General error: 1 all VALUES must have the same number of terms.`
            $attributeKeysPresent = collect($attributesForInsert)->keys()->implode($columnExplodeString);

            // Build array of records for bulk upsert later.
            $recordsToUpsert[$attributeKeysPresent][] = $attributesForInsert;

            // Add the path of this file so we can attach it to the Meta later
            // $pathsToUpsert[] = ltrim(Str::after($path, $source), '/');
        }

        // Upsert the records in bulk
        collect($recordsToUpsert)->each(function ($chunkedRecords, $schemaString) use ($model, $columnExplodeString) {
            collect($chunkedRecords)->chunk(200)->each(function ($chunkedRecordsToUpsert) use ($model, $schemaString, $columnExplodeString) {
                $model::upsert(
                    values: $chunkedRecordsToUpsert->toArray(),
                    uniqueBy: [$model->getKeyName()],
                    update: Str::of($schemaString)->explode($columnExplodeString)->toArray()
                );
            });
        });

        // Get the primary keys of the same amount of recently created models
        $modelsNeedMeta = $model::where('orbit_needs_meta')->get()->pluck($model->getKeyName(), 'orbit_file_path');

        // Delete the Meta matching the keys and morphClass from those records
        $metaToDelete = [];
        foreach ($modelsNeedMeta as $modelData) {
            $metaToDelete[] = [
                'orbital_type' => $model->getMorphClass(),
                'orbital_key' => $modelKey,
            ];
        }

        // Delete the old Meta from the array
        collect($metaToDelete)->chunk(200)->each(function ($chunkedMetaToDelete) use ($model) {
            $query = $model::query();
            foreach ($chunkedMetaToDelete as $record => $wheres) {
                $query->orWhere(function ($q) use ($wheres) {
                    foreach ($wheres as $column => $value) {
                        $q->where($column, $value);
                    }
                });
            }
            $query->delete();
        });

        // Now make an array of Metas combining the model keys and the paths
        $metaToInsert = [];
        $i = 0;
        foreach ($modelsNeedMeta as $modelKey) {
            $metaToInsert[] = [
                'orbital_type' => $model->getMorphClass(),
                'orbital_key' => $modelKey,
                'file_path_read_from' => $pathsToUpsert[$i],
            ];

            $i++;
        }

        // Chunk the upsert
        collect($metaToInsert)->chunk(200)->each(function ($chunkedMetaToInsert) use ($model) {
            Meta::upsert(
                values: $chunkedMetaToInsert->toArray(),
                uniqueBy: ['id'],
                update: ['orbital_type', 'orbital_key', 'file_path_read_from']
            );
        });
    }

    public function orbitMeta(): MorphOne
    {
        return $this->morphOne(Meta::class, 'orbital', id: 'orbital_key');
    }
}
