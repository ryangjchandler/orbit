<?php

namespace Orbit\Drivers;

use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class Markdown implements Driver, ModifiesSchema
{
    public function fromFile(string $path): array
    {
        $document = YamlFrontMatter::parseFile($path);

        return [
            'content' => trim($document->body()),
            ...$document->matter(),
        ];
    }

    public function toFile(array $attributes): string
    {
        $content = $attributes['content'];

        unset($attributes['content']);

        $frontMatter = Yaml::dump($attributes);

        return "---\n" .
            $frontMatter .
            "---\n\n" .
            $content;
    }

    public function extension(): string
    {
        return 'md';
    }

    public function schema(Blueprint $table): void
    {
        if (! $table->hasColumn('content')) {
            $table->longText('content')->nullable();
        }
    }
}
