<?php

declare(strict_types=1);

namespace NyonCode\Ares\Exceptions;

use RuntimeException;

final class InvalidApiResponseException extends RuntimeException
{
    public static function missingRequiredField(string $field): self
    {
        return new self("ARES response is missing required field [{$field}].");
    }

    public static function invalidPayloadType(): self
    {
        return new self('ARES response payload must be an array.');
    }
}
