<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

use NyonCode\Ares\Enums\RegistrationSourceState;

final class RegistrationStatusData
{
    public function __construct(
        public readonly string $source,
        public readonly string $rawStatus,
        public readonly ?RegistrationSourceState $status,
    ) {}
}
