<?php

namespace RyanChandler\FlatFile\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use RyanChandler\FlatFile\Contracts\Driver;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;

class DeleteSourceFile
{
    public function execute(InteractsWithFlatFiles&Model $model, Driver $driver): void
    {
        $directory = config('flat-file.paths.content').DIRECTORY_SEPARATOR.$model->getFlatFileSource();
        $filename = "{$model->getKey()}.{$driver->extension()}";

        $fs = new Filesystem();

        if ($fs->exists($directory.DIRECTORY_SEPARATOR.$filename)) {
            $fs->delete($directory.DIRECTORY_SEPARATOR.$filename);
        }
    }
}
