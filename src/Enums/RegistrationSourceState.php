<?php

declare(strict_types=1);

namespace NyonCode\Ares\Enums;

enum RegistrationSourceState: string
{
    case Active = 'AKTIVNI';
    case Historical = 'HISTORICKY';
    case DoesNotExist = 'NEEXISTUJICI';

    public static function tryFromApi(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
