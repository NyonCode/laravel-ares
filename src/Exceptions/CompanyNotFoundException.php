<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use RuntimeException;

final class CompanyNotFoundException extends RuntimeException
{
    /**
     * Create a new exception for a company not found in ARES.
     *
     * @param  string  $ic  The identification number of the company that was not found
     */
    public static function forIc(string $ic): self
    {
        return new self("Company with IC [{$ic}] was not found in ARES.");
    }
}
