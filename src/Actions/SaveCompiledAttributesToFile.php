<?php

namespace Orbit\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Orbit\Contracts\Driver;
use Orbit\Contracts\Orbit;

class SaveCompiledAttributesToFile
{
    public function execute(Orbit&Model $model, string $compiledAttributes, Driver $driver): void
    {
        $directory = config('orbit.paths.content').DIRECTORY_SEPARATOR.$model->getOrbitSource();
        $filename = "{$model->getKey()}.{$driver->extension()}";

        $fs = new Filesystem();
        $fs->put($directory.DIRECTORY_SEPARATOR.$filename, $compiledAttributes);
    }
}
