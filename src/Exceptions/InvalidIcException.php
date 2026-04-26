<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use InvalidArgumentException;

final class InvalidIcException extends InvalidArgumentException
{
    /**
     * Create a new exception for an invalid IC.
     *
     * @param  string  $ic  The invalid identification number
     */
    public static function forIc(string $ic): self
    {
        return new self("Invalid IC format: {$ic}");
    }
}
