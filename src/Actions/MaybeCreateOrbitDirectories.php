<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Orbit\Contracts\Orbit;
use Orbit\Drivers\FlatJson;

class MaybeCreateOrbitDirectories
{
    public function execute(Orbit&Model $model = null)
    {
        $fs = new Filesystem();

        $fs->ensureDirectoryExists(config('orbit.paths.content'));
        $fs->ensureDirectoryExists(dirname(config('orbit.paths.database')));

        if (! $fs->exists(config('orbit.paths.database'))) {
            $fs->put(config('orbit.paths.database'), '');
        }

        if ($model !== null && $model->getOrbitDriver() !== FlatJson::class) {
            $modelDirectory = config('orbit.paths.content') . DIRECTORY_SEPARATOR . $model->getOrbitSource();

            $fs->ensureDirectoryExists($modelDirectory);
        }
    }
}
