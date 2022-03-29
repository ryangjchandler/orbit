<?php

namespace Orbit\Observers;

use Orbit\Concerns\Orbital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Orbit\Contracts\Driver;

class OrbitalObserver
{
    /** @param Model&Orbital $model */
    public function created(Model $model): void
    {
        $options = $model::getOrbitOptions();

        // 1. We need to get a fresh copy of the model from the database.
        //    This will make sure that any default values defined on the schema
        //    also get saved to the file on-disk.
        $model->refresh();

        $driver = $options->getDriver();

        // 2. The driver is responsible for serialising the model into a
        //    disk-worthy format.
        $serialised = $driver->toFile($this->getModelAttributes($model));

        // 3. With the serialised model ready, we just need to write the file
        //    to disk for manual editing and retrieval later on.

        // TODO: Add support for custom paths here.
        $source = $options->getSource($model);
        $filename = "{$model->getKey()}.{$this->getPrimaryExtensionForDriver($driver)}";

        File::put($source . DIRECTORY_SEPARATOR . $filename, $serialised);
    }

    private function getPrimaryExtensionForDriver(Driver $driver): string
    {
        return Arr::wrap($driver->extension())[0];
    }

    private function getModelAttributes(Model $model)
    {
        // TODO: Do we need to do anything special here for casted values?
        return $model->getAttributes();
    }
}
