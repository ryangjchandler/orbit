<?php

namespace Orbit\Drivers;

use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Orbit\Support\BlueprintUtilities;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class Markdown implements Driver, ModifiesSchema
{
    public function schema(Blueprint $table): void
    {
        if (! BlueprintUtilities::hasColumn($table, 'content')) {
            $table->text('content')->nullable();
        }
    }

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

        return sprintf("---\n%s\n---\n%s", Yaml::dump($attributes), $content);
    }

    public function extension(): string
    {
        return 'md';
    }
}
