<?php

namespace Orbit\Exceptions;

use Exception;

final class InvalidDriverException extends Exception
{
    public static function make(string $driver): self
    {
        return new self("Driver {$driver} is invalid or does not implements the \\Orbit\\Contracts\\Driver interface.");
    }
}
