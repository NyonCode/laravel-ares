<?php

declare(strict_types=1);

namespace NyonCode\Ares\Tests\Fakes;

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\CompanyData;
use RuntimeException;

final class FakeAresClient implements AresClientInterface
{
    /**
     * @var array<string, CompanyData|null>
     */
    public array $companiesByIc = [];

    /**
     * @var list<string>
     */
    public array $findCalls = [];

    /**
     * @var list<string>
     */
    public array $normalizeCalls = [];

    /**
     * @var array<string, string>
     */
    public array $normalizeMap = [];

    /**
     * @var list<string>
     */
    public array $forgottenIcs = [];

    public function findCompany(string $ic): ?CompanyData
    {
        $this->findCalls[] = $ic;

        return $this->companiesByIc[$ic] ?? null;
    }

    public function findCompanyRaw(string $ic): ?array
    {
        return null;
    }

    public function findCompanyOrFail(string $ic): CompanyData
    {
        $company = $this->findCompany($ic);

        if ($company === null) {
            throw new RuntimeException("Company [$ic] not found.");
        }

        return $company;
    }

    public function forgetCompany(string $ic): bool
    {
        $this->forgottenIcs[] = $ic;
        unset($this->companiesByIc[$ic]);

        return true;
    }

    public function isValidIc(string $ic): bool
    {
        return $ic !== '';
    }

    public function normalizeIc(string $ic): string
    {
        $this->normalizeCalls[] = $ic;

        return $this->normalizeMap[$ic] ?? preg_replace('/\s+/', '', $ic) ?? $ic;
    }
}
