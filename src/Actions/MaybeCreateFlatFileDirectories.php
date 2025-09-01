<?php

namespace RyanChandler\FlatFile\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use RyanChandler\FlatFile\Contracts\InteractsWithFlatFiles;

class MaybeCreateFlatFileDirectories
{
    public function execute((InteractsWithFlatFiles&Model)|null $model = null)
    {
        $fs = new Filesystem();

        $fs->ensureDirectoryExists(config('flat-file.paths.content'));
        $fs->ensureDirectoryExists(dirname(config('flat-file.paths.database')));

        if (! $fs->exists(config('flat-file.paths.database'))) {
            $fs->put(config('flat-file.paths.database'), '');
        }

        if ($model !== null) {
            $modelDirectory = config('flat-file.paths.content').DIRECTORY_SEPARATOR.$model->getFlatFileSource();

            $fs->ensureDirectoryExists($modelDirectory);
        }
    }
}
