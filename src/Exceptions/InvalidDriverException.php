<?php

namespace RyanChandler\FlatFile\Exceptions;

use Exception;

final class InvalidDriverException extends Exception
{
    public static function make(string $driver): self
    {
        return new self("Driver {$driver} is invalid or does not implements the \\RyanChandler\\FlatFile\\Contracts\\Driver interface.");
    }
}
