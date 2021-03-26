<?php

namespace Orbit\Drivers;

use SplFileInfo;
use Illuminate\Database\Eloquent\Model;
use Spatie\YamlFrontMatter\YamlFrontMatter;

class MarkdownJson extends Markdown
{
    protected function dumpContent(Model $model): string
    {
        $matter = array_filter($this->getModelAttributes($model), function ($value, $key) {
            return $key !== 'content' && $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        $json = json_encode($matter, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return implode(PHP_EOL, [
            '---',
            rtrim($json, PHP_EOL),
            '---',
            $model->getAttribute('content'),
        ]);
    }

    protected function parseContent(SplFileInfo $file): array
    {
        $content = file_get_contents($file->getPathname());

        $pattern = '/^[\s\r\n]?---[\s\r\n]?$/sm';

        $parts = preg_split($pattern, PHP_EOL . ltrim($content));

        $matter = count($parts) < 3 ? [] : json_decode(trim($parts[1]), true);

        $body = implode(PHP_EOL.'---'.PHP_EOL, array_slice($parts, 2));

        return array_merge(
            $matter,
            ['content' => trim($body)]
        );
    }
}
