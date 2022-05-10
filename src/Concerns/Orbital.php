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

        $recordsToUpsert = [];
        $metaToDelete = [];
        $metaToCreate = [];

        foreach ($files as $file) {

            $path = $file->getPathname();
            $record = new static($driver->fromFile($path));
            $schema = static::resolveConnection()->getSchemaBuilder()->getColumnListing($record->getTable());

            $attributesForInsert = collect($record->getAttributesForInsert())
                ->only($schema)
                ->all();

            // ❕ You have to add files to seperate arrays using their attributes as a key, incase of attributes missing.
            // If not, you get `General error: 1 all VALUES must have the same number of terms.`
            $attributeKeysPresent = collect($attributesForInsert)->keys()->implode('_orbit_column_');

            // Build array of records for bulk upsert later.
            $recordsToUpsert[$attributeKeysPresent][] = $attributesForInsert;

            // Build arrays of Meta data to process later
            $metaToDelete[] = [
                'orbital_type' => $record->getMorphClass(),
                'orbital_key' => $record->getKey()
            ];

            $metaToCreate[] = [
                'orbital_type' => $record->getMorphClass(),
                'orbital_key' => $record->getKey(),
                'file_path_read_from' => ltrim(Str::after($path, $options->getSource($record)), '/'),
            ];
        }

        // Upsert the records in bulk
        collect($recordsToUpsert)->each(function ($chunkedRecords, $schemaString) use ($model) {
            collect($chunkedRecords)->chunk(200)->each(function ($chunkedRecordsToUpsert) use ($model, $schemaString) {
                $model::upsert(
                    values: $chunkedRecordsToUpsert->toArray(),
                    uniqueBy: [$model->getKeyName()],
                    update: Str::of($schemaString)->explode('_orbit_column_')->toArray()
                );
            });
        });

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

        // Create the Meta from the array
        collect($metaToCreate)->chunk(200)->each(function ($chunkedMetaToCreate) use ($model) {
            Meta::upsert(
                values: $chunkedMetaToCreate->toArray(),
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
