<?php

namespace Orbit\Concerns;

use Orbit\Support;
use Orbit\OrbitOptions;
use Orbit\Facades\Orbit;
use Orbit\Contracts\ModifiesSchema;
use Illuminate\Support\Facades\File;
use Orbit\Observers\OrbitalObserver;
use Illuminate\Database\Schema\Blueprint;

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
        $model = new static;
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
}
