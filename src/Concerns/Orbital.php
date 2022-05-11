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

        if (! $force) {
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

    public function orbitMeta(): MorphOne
    {
        return $this->morphOne(Meta::class, 'orbital', id: 'orbital_key');
    }
}
