<?php

namespace Orbit\Contracts;

interface Driver
{
    /** Convert the contents of a file to a valid array of attribues. */
    public function fromFile(string $path): array;

    /** Convert an array of model attributes into a valid string format. */
    public function toFile(array $attributes): string;
}
