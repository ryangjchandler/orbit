<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use SplFileInfo;

class Json extends FileDriver
{
    protected function dumpContent(Model $model): string
    {
        $data = array_filter($this->getModelAttributes($model), fn ($value) => $value !== null);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        $contents = file_get_contents($file->getPathname());

        if (! $contents) {
            return [];
        }

        return json_decode($contents, true);
    }

    protected function extension(): string
    {
        return 'json';
    }
}
