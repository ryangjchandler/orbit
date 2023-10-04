<?php

namespace Orbit\Actions;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;
use Orbit\Support\ConfigureBlueprintFromModel;
use Orbit\Support\FillMissingAttributeValuesFromBlueprint;

class MaybeRefreshDatabaseContent
{
    public function shouldRefresh(Orbit&Model $model): bool
    {
        $databaseMTime = filemtime(config('orbit.paths.database'));
        $directory = config('orbit.paths.content') . DIRECTORY_SEPARATOR . $model->getOrbitSource();
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
        $directory = config('orbit.paths.content') . DIRECTORY_SEPARATOR . $model->getOrbitSource();
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
                $model->query()->whereKey($chunk->pluck($model->getKey())->all())->delete();

                $model->query()->insert(
                    $chunk
                        ->map(function (array $attributes) use ($model, $blueprint) {
                            foreach ($attributes as $key => $value) {
                                $model->setAttribute($key, $value);
                            }

                            $attributes = array_filter($model->getAttributes(), fn (string $key) => array_key_exists($key, $attributes), ARRAY_FILTER_USE_KEY);

                            return FillMissingAttributeValuesFromBlueprint::fill($attributes, $blueprint);
                        })
                        ->all()
                );
            });
    }
}
