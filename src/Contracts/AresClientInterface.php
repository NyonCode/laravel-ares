<?php

declare(strict_types=1);

namespace NyonCode\Ares\Contracts;

use Illuminate\Support\Collection;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Data\SubjectData;

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

    /**
     * Search indexed subjects by name or IC for autocomplete.
     *
     * @return Collection<int, SubjectData>
     */
    public function search(string $query, int $limit = 10): Collection;
}
