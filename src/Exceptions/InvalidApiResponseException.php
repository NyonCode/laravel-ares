<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use RuntimeException;

final class InvalidApiResponseException extends RuntimeException
{
    /**
     * Create a new exception for a missing required field in ARES response.
     *
     * @param  string  $field  The name of the missing field
     */
    public static function missingRequiredField(string $field): self
    {
        return new self("ARES response is missing required field [{$field}].");
    }

    /**
     * Create a new exception for an invalid payload type in ARES response.
     */
    public static function invalidPayloadType(): self
    {
        return new self('ARES response payload must be an array.');
    }
}
