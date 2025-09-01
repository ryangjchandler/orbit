<?php

namespace RyanChandler\FlatFile\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\Orbit;

class DeleteSourceFile
{
    public function execute(Orbit&Model $model, Driver $driver): void
    {
        $directory = config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();
        $filename = "{$model->getKey()}.{$driver->extension()}";

        $fs = new Filesystem();

        if ($fs->exists($directory.DIRECTORY_SEPARATOR.$filename)) {
            $fs->delete($directory.DIRECTORY_SEPARATOR.$filename);
        }
    }
}
