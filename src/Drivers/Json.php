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
        return json_encode($attributes, JSON_PRETTY_PRINT);
    }

    public function extension(): string
    {
        return 'json';
    }
}
