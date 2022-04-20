<?php

namespace Orbit\Drivers;

use Orbit\Contracts\Driver;

class Json implements Driver
{
    public function fromFile(string $path): array
    {
        $contents = file_get_contents($path);

        if (! $contents) {
            return [];
        }

        return json_decode($contents, associative: true);
    }

    public function toFile(array $attributes): string
    {
        return json_encode($attributes, JSON_PRETTY_PRINT);
    }

    public function extension(): string
    {
        return 'json';
    }
}
