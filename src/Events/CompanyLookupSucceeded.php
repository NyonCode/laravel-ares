<?php

declare(strict_types=1);

namespace NyonCode\Ares\Events;

use NyonCode\Ares\Data\CompanyData;

final class CompanyLookupSucceeded
{
    public function __construct(public readonly CompanyData $company) {}
}
