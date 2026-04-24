<?php

declare(strict_types=1);

namespace NyonCode\Ares\Events;

use Throwable;

final class CompanyLookupFailed
{
    public function __construct(
        public readonly string $ic,
        public readonly int $httpStatus = 0,
        public readonly ?Throwable $exception = null,
    ) {}
}
