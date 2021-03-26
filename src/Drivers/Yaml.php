<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

class Yaml extends FileDriver
{
    protected function dumpContent(Model $model): string
    {
        $data = array_filter($this->getModelAttributes($model));

        return SymfonyYaml::dump($data);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        return SymfonyYaml::parseFile($file->getPathname());
    }

    protected function extension(): string
    {
        return 'yml';
    }
}
