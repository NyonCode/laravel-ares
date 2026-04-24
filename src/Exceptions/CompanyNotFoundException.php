<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use RuntimeException;

final class CompanyNotFoundException extends RuntimeException
{
    public static function forIc(string $ic): self
    {
        return new self("Company with ICO [{$ic}] was not found in ARES.");
    }
}
