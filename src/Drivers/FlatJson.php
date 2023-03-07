<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SplFileInfo;

class FlatJsonDriver extends FileDriver
{
    public function shouldRestoreCache(string $directory): bool
    {
        $file = new SplFileInfo($this->filepath($directory));
        $highest = $file->isFile() ? $file->getMTime() : 0;

        return $highest > filemtime(Orbit::getDatabasePath());
    }
    
    public function save(Model $model, string $directory): bool
    {
        $existing = $this->all($directory)
            ->keyBy($model->getKeyName())
            ->put(
                $model->getKey(),
                array_filter($this->getModelAttributes($model), fn ($value) => $value !== null)
            );

        file_put_contents($this->filepath($directory), $existing->toJson(JSON_PRETTY_PRINT));

        return true;
    }

    public function delete(Model $model, string $directory): bool
    {
        $existing = $this->all($directory)->keyBy($model->getKeyName());

        file_put_contents($this->filepath($directory), $existing->toJson(JSON_PRETTY_PRINT));

        return true;
    }

    public function all(string $directory): Collection
    {
        $file = new SplFileInfo($this->filepath($directory));

        return Collection::make($this->parseContent($file))
            ->each(fn ($value) => $value['file_path_read_from'] = $file->getRealPath());
    }

    public function filepath(string $directory, string $key = ''): string
    {
        return $directory . '.' . $this->extension();
    }

    protected function extension(): string
    {
        return 'json';
    }

    protected function dumpContent(Model $model): string
    {
        $data = array_filter($this->getModelAttributes($model), fn ($value) => $value !== null);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        if (!$file->isFile()) {
            file_put_contents($file->getPathname(), '');
        }
        
        $contents = file_get_contents($file->getPathname());

        if (! $contents) {
            return [];
        }

        return json_decode($contents, true);
    }
}
