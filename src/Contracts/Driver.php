<?php

namespace Orbit\Contracts;

interface Driver
{
    /** Convert the contents of a file to a valid array of attribues. */
    public function fromFile(string $path): array;

    /** Convert an array of model attributes into a valid string format. */
    public function toFile(array $attributes): string;

    /** The file extension(s) that this driver supports and recognises. */
    public function extension(): string;
}
