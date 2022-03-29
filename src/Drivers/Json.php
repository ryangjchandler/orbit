<?php

namespace Orbit\Drivers;

use Orbit\Contracts\Driver;

class Json implements Driver
{
    public function fromFile(string $path): array
    {
        return [];
    }

    public function toFile(array $attributes): string
    {
        return '';
    }

    public function extension(): string|array
    {
        return ['json'];
    }
}
