<?php

namespace Orbit\Drivers;

use Orbit\Contracts\Driver;
use Symfony\Component\Yaml\Yaml as YamlYaml;

class Yaml implements Driver
{
    public function fromFile(string $path): array
    {
        return YamlYaml::parseFile($path);
    }

    public function toFile(array $attributes): string
    {
        return YamlYaml::dump($attributes);
    }

    public function extension(): string
    {
        return 'yml';
    }
}
