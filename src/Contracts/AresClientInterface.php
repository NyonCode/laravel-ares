<?php

declare(strict_types=1);

namespace NyonCode\Ares\Contracts;

use NyonCode\Ares\Data\CompanyData;

interface AresClientInterface
{
    public function findCompany(string $ic): ?CompanyData;

    /**
     * @return array<string, mixed>|null
     */
    public function findCompanyRaw(string $ic): ?array;

    public function findCompanyOrFail(string $ic): CompanyData;

    public function forgetCompany(string $ic): bool;

    public function isValidIc(string $ic): bool;

    public function normalizeIc(string $ic): string;
}
