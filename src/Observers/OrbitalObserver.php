<?php

namespace Orbit\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Orbit\Concerns\Orbital;
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

    /** @param Model&Orbital $model */
    public function updated(Model $model): void
    {
        $options = $model->getOrbitOptions();
        $source = $options->getSource($model);
        $driver = $options->getDriver();
        $filename = "{$model->getKey()}.{$this->getPrimaryExtensionForDriver($driver)}";

        // 1. In some cases, the primary key of a record might change during a save.
        //    If that does happen, we need to clean things up and remove the old file.
        if ($model->wasChanged($model->getKeyName())) {
            $oldFilename = "{$model->getOriginal($model->getKeyName())}.{$this->getPrimaryExtensionForDriver($driver)}";

            File::delete($source . DIRECTORY_SEPARATOR . $oldFilename);
        }

        // 2. We can then write to the new file, storing the updated contents of the model.
        $serialised = $driver->toFile($this->getModelAttributes($model));

        File::put($source . DIRECTORY_SEPARATOR . $filename, $serialised);
    }

    /** @param Model&Orbital $model */
    public function deleted(Model $model): void
    {
        $options = $model->getOrbitOptions();
        $source = $options->getSource($model);
        $driver = $options->getDriver();
        $filename = "{$model->getKey()}.{$this->getPrimaryExtensionForDriver($driver)}";

        // 1. We just need to delete the file here. Nothing special at all.
        File::delete($source . DIRECTORY_SEPARATOR . $filename);
    }

    private function getPrimaryExtensionForDriver(Driver $driver): string
    {
        return Arr::wrap($driver->extension())[0];
    }

    private function getModelAttributes(Model $model)
    {
        // TODO: Do we need to do anything special here for casted values?
        return collect($model->getAttributes())
            ->map(fn ($_, string $key) => $model->{$key})
            ->toArray();
    }
}
