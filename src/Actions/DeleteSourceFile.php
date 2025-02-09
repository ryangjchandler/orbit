<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\FlatJson;

class DeleteSourceFile
{
    public function execute(Orbit&Model $model, Driver $driver): void
    {
        $directory = config('orbit.paths.content') . DIRECTORY_SEPARATOR . $model->getOrbitSource();
        $fs = new Filesystem();

        if ($driver instanceof FlatJson) {
            $filename = $directory . '.' . $driver->extension();
            $contents = $fs->exists($filename) ? $fs->get($filename) : '{}';
            $records = collect($driver->parse($contents))
                ->keyBy($model->getKeyName())
                ->forget($model->getKey())
                ->all();

            $fs->put($filename, $driver->compile($records));

            return;
        }

        $filename = "{$model->getKey()}.{$driver->extension()}";

        if ($fs->exists($directory . DIRECTORY_SEPARATOR . $filename)) {
            $fs->delete($directory . DIRECTORY_SEPARATOR . $filename);
        }
    }
}
