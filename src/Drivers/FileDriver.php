<?php

namespace Orbit\Drivers;

use FilesystemIterator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orbit\Facades\Orbit;
use Orbit\Contracts\Driver as DriverContract;
use SplFileInfo;

abstract class FileDriver implements DriverContract
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
        if ($model->wasChanged($model->getKeyName())) {
            unlink($this->filepath($directory, $model->getOriginal($model->getKeyName())));
        }

        $path = $this->filepath($directory, $model->getKey());

        if (! file_exists($path)) {
            file_put_contents($path, '');
        }

        file_put_contents($path, $this->dumpContent($model));

        return true;
    }

    public function delete(Model $model, string $directory): bool
    {
        unlink($this->filepath($directory, $model->getKey()));

        return true;
    }

    public function all(string $directory): Collection
    {
        $collection = Collection::make();
        $files = new FilesystemIterator($directory);

        foreach ($files as $file) {
            if ($file->getExtension() !== $this->extension()) continue;

            $collection->push($this->parseContent($file));
        }

        return $collection;
    }

    protected function filepath(string $directory, string $key): string
    {
        return $directory . DIRECTORY_SEPARATOR . $key . '.' . $this->extension();
    }

    protected function getModelAttributes(Model $model)
    {
        return collect($model->getAttributes())
            ->map(fn ($_, $key) => $model->{$key})
            ->toArray();
    }

    abstract protected function extension(): string;

    abstract protected function dumpContent(Model $model): string;

    abstract protected function parseContent(SplFileInfo $file): array;
}
