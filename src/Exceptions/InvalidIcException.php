<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use InvalidArgumentException;

final class InvalidIcException extends InvalidArgumentException
{
    public static function forIc(string $ic): self
    {
        return new self("Invalid ICO [{$ic}].");
    }
}
