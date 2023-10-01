<?php

namespace Orbit\Contracts;

use SplFileInfo;

interface Driver
{
    /**
     * Use the given file to generate an array of model attributes.
     */
    public function parse(string $fileContents): array;

    /**
     * Use the given array of attributes to generate the contents of a file.
     */
    public function compile(array $attributes): string;
}
