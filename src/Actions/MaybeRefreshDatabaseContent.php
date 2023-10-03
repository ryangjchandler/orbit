<?php

namespace Orbit\Actions;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;
use Orbit\Support\ConfigureBlueprintFromModel;
use Orbit\Support\FillMissingAttributeValues;
use Orbit\Support\FillMissingAttributeValuesFromBlueprint;
use ReflectionClass;

class MaybeRefreshDatabaseContent
{
    public function shouldRefresh(Orbit&Model $model): bool
    {
        $databaseMTime = filemtime(config('orbit.paths.database'));
        $directory = config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();
        $highestMTime = 0;

        foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $file) {
            if ($file->getMTime() >= $highestMTime) {
                $highestMTime = $file->getMTime();
            }
        }

        return $highestMTime >= $databaseMTime;
    }

    public function refresh(Orbit&Model $model, Driver $driver): void
    {
        $model->query()->truncate();

        $directory = config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();
        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
        $records = [];

        foreach ($iterator as $file) {
            $contents = file_get_contents($file->getRealPath());
            $records[] = $driver->parse($contents);
        }

        $blueprint = ConfigureBlueprintFromModel::configure(
            $model,
            new Blueprint($model->getTable())
        );

        collect($records)
            ->chunk(100)
            ->each(function (Collection $chunk) use ($model, $blueprint) {
                // This will ensure that we don't have any collisions with existing data in the SQLite database.
                $model->query()->whereKey($chunk->pluck($model->getKey()))->delete();

                $model->insert(
                    $chunk
                        ->map(fn (array $attributes) => FillMissingAttributeValuesFromBlueprint::fill($attributes, $blueprint))
                        ->all()
                );
            });
    }
}
