<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use SplFileInfo;

class Json extends FileDriver
{
    protected function dumpContent(Model $model): string
    {
        $data = array_filter($model->attributesToArray());

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        return json_decode(file_get_contents($file->getPathname()), true);
    }

    protected function extension(): string
    {
        return 'json';
    }
}
