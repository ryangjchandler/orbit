<?php

namespace Orbit\Exceptions;

use Exception;

class InvalidDriverException extends Exception
{
    public static function make(string $driver): static
    {
        return new static("Driver {$driver} is invalid or does not implements the \\Orbit\\Contracts\\Driver interface.");
    }
}
