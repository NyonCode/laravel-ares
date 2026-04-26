<?php

declare(strict_types=1);

namespace NyonCode\Ares\Helpers;

use BadMethodCallException;
use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\CompanyData;

final class AresFluentBuilder
{
    private ?CompanyData $company = null;

    /**
     * @var list<CompanyData>|null
     */
    private ?array $companies = null;

    /**
     * @var array<string, CompanyData|null>|null
     */
    private ?array $results = null;

    public function __construct(private readonly AresClientInterface $client) {}

    /**
     * @param  list<mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (method_exists($this->client, $method)) {
            return $this->client->{$method}(...$arguments);
        }

        if (method_exists(AresHelper::class, $method)) {
            return AresHelper::{$method}(...$arguments);
        }

        throw new BadMethodCallException(sprintf('Method [%s] does not exist on AresFluentBuilder.', $method));
    }

    /**
     * Find a company by IC.
     *
     * @param  string  $ic  The company identification number
     */
    public function find(string $ic): self
    {
        $this->reset();
        $this->company = $this->client->findCompany($ic);

        return $this;
    }

    /**
     * Find multiple companies by ICs.
     *
     * @param  array<string>  $ics  Array of identification numbers
     */
    public function findMany(array $ics): self
    {
        $this->reset();
        $this->results = [];

        foreach ($ics as $ic) {
            $this->results[$ic] = $this->client->findCompany($ic);
        }

        $this->companies = $this->resolvedCompaniesFromResults();

        return $this;
    }

    /**
     * Find company or throw exception.
     *
     * @param  string  $ic  The company identification number
     */
    public function findOrFail(string $ic): self
    {
        $this->reset();
        $this->company = $this->client->findCompanyOrFail($ic);

        return $this;
    }

    /**
     * Filter to active companies only.
     */
    public function active(): self
    {
        if ($this->company !== null) {
            if (! AresHelper::isCompanyActive($this->company)) {
                $this->company = null;
            }
        } elseif ($this->companies !== null) {
            $this->companies = array_values(AresHelper::filterActiveCompanies($this->companies));
        }

        return $this;
    }

    /**
     * Filter to inactive companies only.
     */
    public function inactive(): self
    {
        if ($this->company !== null) {
            if (AresHelper::isCompanyActive($this->company)) {
                $this->company = null;
            }
        } elseif ($this->companies !== null) {
            $this->companies = array_values(array_filter(
                $this->companies,
                static fn (CompanyData $company): bool => ! AresHelper::isCompanyActive($company)
            ));
        }

        return $this;
    }

    /**
     * Filter by legal form.
     *
     * @param  string  $legalForm  The legal form to filter by
     */
    public function legalForm(string $legalForm): self
    {
        if ($this->company !== null) {
            if (AresHelper::getLegalForm($this->company) !== $legalForm) {
                $this->company = null;
            }
        } elseif ($this->companies !== null) {
            $this->companies = array_values(AresHelper::filterByLegalForm($this->companies, $legalForm));
        }

        return $this;
    }

    /**
     * Filter to companies with VAT numbers only.
     */
    public function withVat(): self
    {
        if ($this->company !== null) {
            if (! AresHelper::hasVatNumber($this->company)) {
                $this->company = null;
            }
        } elseif ($this->companies !== null) {
            $this->companies = array_values(array_filter(
                $this->companies,
                static fn (CompanyData $company): bool => AresHelper::hasVatNumber($company)
            ));
        }

        return $this;
    }

    /**
     * Filter to companies without VAT numbers only.
     */
    public function withoutVat(): self
    {
        if ($this->company !== null) {
            if (AresHelper::hasVatNumber($this->company)) {
                $this->company = null;
            }
        } elseif ($this->companies !== null) {
            $this->companies = array_values(array_filter(
                $this->companies,
                static fn (CompanyData $company): bool => ! AresHelper::hasVatNumber($company)
            ));
        }

        return $this;
    }

    /**
     * Search companies by name.
     *
     * @param  string  $searchTerm  The search term
     * @param  bool  $caseSensitive  Whether search should be case sensitive
     */
    public function search(string $searchTerm, bool $caseSensitive = false): self
    {
        if ($this->companies !== null) {
            $this->companies = array_values(AresHelper::searchByName($this->companies, $searchTerm, $caseSensitive));
        }

        return $this;
    }

    /**
     * Limit the number of results.
     *
     * @param  int  $limit  Maximum number of results
     */
    public function limit(int $limit): self
    {
        if ($this->companies !== null) {
            $this->companies = array_slice($this->companies, 0, $limit);
        }

        return $this;
    }

    /**
     * Skip a number of results.
     *
     * @param  int  $offset  Number of results to skip
     */
    public function offset(int $offset): self
    {
        if ($this->companies !== null) {
            $this->companies = array_slice($this->companies, $offset);
        }

        return $this;
    }

    /**
     * Get the first result.
     */
    public function first(): self
    {
        if ($this->companies !== null) {
            $firstCompany = reset($this->companies);
            $this->company = $firstCompany instanceof CompanyData ? $firstCompany : null;
            $this->companies = null;
        }

        return $this;
    }

    /**
     * Get results as array of CompanyData objects.
     *
     * @return array<CompanyData>
     */
    public function get(): array
    {
        if ($this->company !== null) {
            return [$this->company];
        }

        return $this->companies ?? [];
    }

    /**
     * Get results as formatted display arrays.
     *
     * @return array<array<string, string>>
     */
    public function getFormatted(): array
    {
        $companies = $this->get();

        return array_map(function (CompanyData $company) {
            return AresHelper::formatCompanyForDisplay($company);
        }, $companies);
    }

    /**
     * Get the first company or null.
     */
    public function firstOrFail(): ?CompanyData
    {
        $companies = $this->get();

        return $companies[0] ?? null;
    }

    /**
     * Get the company (for single company operations).
     */
    public function company(): ?CompanyData
    {
        return $this->company;
    }

    /**
     * Check if any results exist.
     */
    public function exists(): bool
    {
        if ($this->company !== null) {
            return true;
        }

        return ! empty($this->companies);
    }

    /**
     * Check if no results exist.
     */
    public function isEmpty(): bool
    {
        return ! $this->exists();
    }

    /**
     * Count the number of results.
     */
    public function count(): int
    {
        if ($this->company !== null) {
            return 1;
        }

        return count($this->companies ?? []);
    }

    /**
     * Get statistics for the current results.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $companies = $this->get();

        return AresHelper::getCompanyStatistics($companies);
    }

    /**
     * Get company names as array.
     *
     * @return array<string>
     */
    public function names(): array
    {
        $companies = $this->get();

        return array_map(function (CompanyData $company) {
            return $company->name;
        }, $companies);
    }

    /**
     * Get company ICs as array.
     *
     * @return array<string>
     */
    public function ics(): array
    {
        $companies = $this->get();

        return array_map(function (CompanyData $company) {
            return $company->ic;
        }, $companies);
    }

    /**
     * Get company addresses as array.
     *
     * @return array<string>
     */
    public function addresses(): array
    {
        $companies = $this->get();

        return array_map(function (CompanyData $company) {
            return AresHelper::getFullAddress($company);
        }, $companies);
    }

    /**
     * Get companies as key-value pairs (IC => CompanyData).
     *
     * @return array<string, CompanyData>
     */
    public function keyByIc(): array
    {
        $companies = $this->get();

        $result = [];
        foreach ($companies as $company) {
            $result[$company->ic] = $company;
        }

        return $result;
    }

    /**
     * Get companies as key-value pairs (IC => formatted array).
     *
     * @return array<string, array<string, string>>
     */
    public function keyByIcFormatted(): array
    {
        $companies = $this->get();

        $result = [];
        foreach ($companies as $company) {
            $result[$company->ic] = AresHelper::formatCompanyForDisplay($company);
        }

        return $result;
    }

    /**
     * Clear cache for current IC(s).
     */
    public function forget(): self
    {
        if ($this->company !== null) {
            $this->client->forgetCompany($this->company->ic);
        } elseif ($this->results !== null) {
            foreach (array_keys($this->results) as $ic) {
                $this->client->forgetCompany($ic);
            }
        }

        return $this;
    }

    /**
     * Reset the builder state.
     */
    public function reset(): self
    {
        $this->company = null;
        $this->companies = null;
        $this->results = null;

        return $this;
    }

    /**
     * @return list<CompanyData>
     */
    private function resolvedCompaniesFromResults(): array
    {
        if ($this->results === null) {
            return [];
        }

        $companies = [];

        foreach ($this->results as $company) {
            if ($company instanceof CompanyData) {
                $companies[] = $company;
            }
        }

        return $companies;
    }

    public function client(): AresClientInterface
    {
        return $this->client;
    }
}
