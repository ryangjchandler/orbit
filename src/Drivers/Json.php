<?php

namespace Orbit\Drivers;

use Orbit\Contracts\Driver;

class Json implements Driver
{
    public function parse(string $fileContents): array
    {
        return json_decode($fileContents, associative: true);
    }

    public function compile(array $attributes): string
    {
        // There's no way to represent `null` in JSON, so we filter them out and
        // then they will get added back in when necessary.
        $attributes = array_filter($attributes, fn ($value) => $value !== null);

        return json_encode($attributes, JSON_PRETTY_PRINT);
    }

    public function extension(): string
    {
        return 'json';
    }
}
