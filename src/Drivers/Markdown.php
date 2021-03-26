<?php

namespace Orbit\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Markdown extends FileDriver
{
    protected static $contentColumn = 'content';

    protected function dumpContent(Model $model): string
    {
        $matter = array_filter($this->getModelAttributes($model), function ($value, $key) {
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

    public function schema(Blueprint $table)
    {
        if (! $table->hasColumn(static::getContentColumn())) {
            $table->text(static::getContentColumn())->nullable();
        }
    }

    public static function contentColumn(string $name = 'content')
    {
        static::$contentColumn = $name;
    }

    public static function getContentColumn()
    {
        return static::$contentColumn;
    }
}
