<?php

namespace Orbit\Drivers;

use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;
use Symfony\Component\Yaml\Yaml;

class Markdown implements Driver, ModifiesSchema
{
    public function fromFile(string $path): array
    {
        return [];
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

    public function extension(): string|array
    {
        return ['md', 'markdown'];
    }

    public function schema(Blueprint $table): void
    {
        $table->longText('content')->nullable();
    }
}
