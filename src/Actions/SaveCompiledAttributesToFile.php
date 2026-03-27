<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\FlatJson;

class SaveCompiledAttributesToFile
{
    public function execute(Orbit&Model $model, string $compiledAttributes, Driver $driver): void
    {
        $directory = config('orbit.paths.content') . DIRECTORY_SEPARATOR . $model->getOrbitSource();
        $fs = new Filesystem();

        if ($driver instanceof FlatJson) {
            $filename = $directory . '.' . $driver->extension();

            $contents = $fs->exists($filename) ? $fs->get($filename) : '{}';
            $rows = collect($driver->parse($contents))
                ->keyBy($model->getKeyName())
                ->put(
                    $model->getKey(),
                    $driver->parse($compiledAttributes),
                )
                ->all();

            $fs->put($filename, $driver->compile($rows));
            return;
        }

        $filename = "{$model->getKey()}.{$driver->extension()}";

        if ($model->wasChanged($model->getKey())) {
            $fs->delete($directory . DIRECTORY_SEPARATOR . $model->getOriginal($model->getKeyName()) . '.' . $driver->extension());
        }

        $fs->put($directory . DIRECTORY_SEPARATOR . $filename, $compiledAttributes);
    }
}
