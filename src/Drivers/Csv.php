<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use SplFileInfo;

class Csv extends FileDriver
{
    protected function dumpContent(Model $model): string
    {
        $data = array_filter($this->getModelAttributes($model));

        return implode(',', $data);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        $path = $file->getPathname();

        try {
            $handle = fopen($path, 'r');
            $data = fgetcsv($handle);
        } catch (\Exception $e) {
            return [];
        } finally {
            fclose($handle);
        }

        return $data;
    }

    protected function extension(): string
    {
        return 'csv';
    }
}
