<?php

namespace Orbit\Drivers;

use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class Markdown extends Driver
{
    public function parse(string $fileContents): array
    {
        $document = YamlFrontMatter::parse($fileContents);

        return [
            ...$document->matter(),
            'content' => trim($document->body()),
        ];
    }

    public function compile(array $attributes): string
    {
        $content = $attributes['content'] ?? null;

        unset($attributes['content']);

        return sprintf("---\n%s\n---\n\n%s", Yaml::dump($attributes), $content);
    }

    public function extension(): string
    {
        return 'md';
    }
}
