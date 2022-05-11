<?php

namespace Orbit\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Orbit\Contracts\Driver;
use Orbit\Contracts\IsOrbital;
use Orbit\Support;

class OrbitalObserver
{
    public function created(Model & IsOrbital $model): void
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
        $filename = Support::generateFilename($model, $options, $driver);

        $model->orbitMeta()->create([
            'file_path_read_from' => $filename,
        ]);

        File::ensureDirectoryExists(dirname($source . DIRECTORY_SEPARATOR . $filename));
        File::put($source . DIRECTORY_SEPARATOR . $filename, $serialised);
    }

    public function updated(Model & IsOrbital $model): void
    {
        $options = $model::getOrbitOptions();
        $source = $options->getSource($model);
        $driver = $options->getDriver();
        $filename = Support::generateFilename($model, $options, $driver);

        // 1. In some cases, the primary key of a record might change during a save.
        //    If that does happen, we need to clean things up and remove the old file.
        if ($model->orbitMeta->file_path_read_from !== $filename) {
            File::delete($source . DIRECTORY_SEPARATOR . $model->orbitMeta->file_path_read_from);
        }

        // 2. We can then write to the new file, storing the updated contents of the model.
        $serialised = $driver->toFile($this->getModelAttributes($model));

        File::ensureDirectoryExists(dirname($source . DIRECTORY_SEPARATOR . $filename));
        File::put($source . DIRECTORY_SEPARATOR . $filename, $serialised);

        $model->orbitMeta->update([
            'file_path_read_from' => $filename,
        ]);
    }

    public function deleted(Model & IsOrbital $model): void
    {
        $options = $model::getOrbitOptions();
        $source = $options->getSource($model);
        $filename = $model->orbitMeta->file_path_read_from;

        // 1. We just need to delete the file here. Nothing special at all.
        File::delete($source . DIRECTORY_SEPARATOR . $filename);
    }

    private function getModelAttributes(Model & IsOrbital $model)
    {
        // TODO: Do we need to do anything special here for casted values?
        return collect($model->getAttributes())
            ->map(fn ($_, string $key) => $model->{$key})
            ->toArray();
    }
}
