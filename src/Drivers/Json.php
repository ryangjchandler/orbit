<?php

namespace Orbit\Drivers;

use FilesystemIterator;
use Orbit\Facades\Orbit;
use Orbit\Contracts\Driver;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Json implements Driver
{
    public function shouldRestoreCache(string $directory): bool
    {
        $highest = 0;

        foreach (new FilesystemIterator($directory) as $file) {
            if ($file->getMTime() > $highest) {
                $highest = $file->getMTime();
            }
        }

        return $highest > filemtime(Orbit::getDatabasePath());
    }

    public function save(Model $model, string $directory): bool
    {
        $key = $model->getKey();

        if ($model->wasChanged($model->getKeyName())) {
            unlink($directory . DIRECTORY_SEPARATOR . $model->getOriginal($model->getKeyName()) . '.json');
        }

        if (! file_exists($path = $directory . DIRECTORY_SEPARATOR . $key . '.json')) {
            file_put_contents($path, '');
        }

        $data = array_filter($model->attributesToArray());

        $json = json_encode($data, JSON_PRETTY_PRINT);

        file_put_contents($path, $json);

        return true;
    }

    public function delete(Model $model, string $directory): bool
    {
        $key = $model->getKey();

        unlink($directory . DIRECTORY_SEPARATOR . $key . '.json');

        return true;
    }

    public function all(string $directory): Collection
    {
        $collection = Collection::make();
        $files = new FilesystemIterator($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'json') continue;

            $collection->push(
                json_decode(file_get_contents($file->getPathname()), true)
            );
        }

        return $collection;
    }
}
