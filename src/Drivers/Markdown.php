<?php

namespace Orbit\Drivers;

use Illuminate\Database\Schema\Blueprint;
use Orbit\Contracts\Driver;
use Orbit\Contracts\ModifiesSchema;

class Markdown implements Driver, ModifiesSchema
{
    public function fromFile(string $path): array
    {
        return [];
    }

    public function toFile(array $attributes): string
    {
        return '';
    }

    public function schema(Blueprint $table): void
    {
        $table->longText('content')->nullable();
    }
}
