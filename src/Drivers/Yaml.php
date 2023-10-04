<?php

namespace Orbit\Drivers;

use Orbit\Contracts\Driver;
use Symfony\Component\Yaml\Yaml as YamlParser;

class Yaml implements Driver
{
    public function parse(string $fileContents): array
    {
        return YamlParser::parse($fileContents);
    }

    public function compile(array $attributes): string
    {
        return YamlParser::dump($attributes);
    }

    public function extension(): string
    {
        return 'yml';
    }
}
