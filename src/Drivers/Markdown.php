<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Markdown extends FileDriver
{
    protected function dumpContent(Model $model): string
    {
        $matter = array_filter($model->attributesToArray(), function ($value, $key) {
            return $key !== 'content' && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        $yaml = Yaml::dump($matter);

        return implode(PHP_EOL, [
            '---',
            rtrim($yaml, PHP_EOL),
            '---',
            $model->getAttribute('content'),
        ]);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        $document = YamlFrontMatter::parseFile($file->getPathname());

        return array_merge(
            $document->matter(),
            ['content' => trim($document->body())]
        );
    }

    protected function extension(): string
    {
        return 'md';
    }
}
