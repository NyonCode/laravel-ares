<?php

declare(strict_types=1);

namespace NyonCode\Ares\Helpers;

use NyonCode\Ares\Contracts\AresClientInterface;
use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Enums\RegistrationSourceState;
use NyonCode\Ares\Facades\Ares;

final class AresHelper
{
    /**
     * Check if a company is active based on registration status.
     *
     * @param  CompanyData  $company  The company data
     * @return bool True if the company is active, false otherwise
     */
    public static function isCompanyActive(CompanyData $company): bool
    {
        $primarySource = $company->registration->primarySource;

        if ($primarySource !== null) {
            $primaryStatus = $company->registration->sourceStatus($primarySource)?->status;

            if ($primaryStatus !== null) {
                return $primaryStatus === RegistrationSourceState::Active;
            }
        }

        foreach ($company->registration->sourceStatuses as $sourceStatus) {
            if ($sourceStatus->status === RegistrationSourceState::Active) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the full formatted address of a company.
     *
     * @param  CompanyData  $company  The company data
     * @return string The formatted address or 'N/A' if not available
     */
    public static function getFullAddress(CompanyData $company): string
    {
        if ($company->registeredOffice === null) {
            return 'N/A';
        }

        return $company->registeredOffice->formatted ?? 'N/A';
    }

    /**
     * Get the company's legal form in a readable format.
     *
     * @param  CompanyData  $company  The company data
     * @return string The legal form or 'N/A' if not available
     */
    public static function getLegalForm(CompanyData $company): string
    {
        return $company->registration->legalForm ?? 'N/A';
    }

    /**
     * Check if a company has a valid VAT number (DIC).
     *
     * @param  CompanyData  $company  The company data
     * @return bool True if the company has a VAT number, false otherwise
     */
    public static function hasVatNumber(CompanyData $company): bool
    {
        return ! empty($company->dic);
    }

    /**
     * Get the company's establishment date in a formatted way.
     *
     * @param  CompanyData  $company  The company data
     * @param  string  $format  The date format (default: Y-m-d)
     * @return string The formatted date or 'N/A' if not available
     */
    public static function getEstablishmentDate(CompanyData $company, string $format = 'Y-m-d'): string
    {
        if ($company->registration->dateOfEstablishment === null) {
            return 'N/A';
        }

        try {
            $date = new \DateTime($company->registration->dateOfEstablishment);

            return $date->format($format);
        } catch (\Exception) {
            return 'N/A';
        }
    }

    /**
     * Validate multiple IC numbers and return the valid ones with their data.
     *
     * @param  array<string>  $ics  Array of identification numbers
     * @return array<string, CompanyData|null> Array with IC as key and CompanyData or null as value
     */
    public static function validateMultipleIcs(array $ics): array
    {
        $results = [];

        foreach ($ics as $ic) {
            $results[$ic] = Ares::findCompany($ic);
        }

        return $results;
    }

    /**
     * Filter companies by legal form.
     *
     * @param  array<CompanyData>  $companies  Array of companies
     * @param  string  $legalForm  The legal form to filter by
     * @return array<CompanyData> Filtered companies
     */
    public static function filterByLegalForm(array $companies, string $legalForm): array
    {
        return array_values(array_filter($companies, function (CompanyData $company) use ($legalForm) {
            return self::getLegalForm($company) === $legalForm;
        }));
    }

    /**
     * Filter active companies from an array.
     *
     * @param  array<CompanyData>  $companies  Array of companies
     * @return array<CompanyData> Active companies only
     */
    public static function filterActiveCompanies(array $companies): array
    {
        return array_values(array_filter($companies, function (CompanyData $company) {
            return self::isCompanyActive($company);
        }));
    }

    /**
     * Get company statistics from an array of companies.
     *
     * @param  array<CompanyData>  $companies  Array of companies
     * @return array<string, mixed> Statistics about the companies
     */
    public static function getCompanyStatistics(array $companies): array
    {
        $total = count($companies);
        $active = count(self::filterActiveCompanies($companies));
        $withVat = count(array_filter($companies, function (CompanyData $company) {
            return self::hasVatNumber($company);
        }));

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'with_vat' => $withVat,
            'without_vat' => $total - $withVat,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'vat_percentage' => $total > 0 ? round(($withVat / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Format company information as an array for display purposes.
     *
     * @param  CompanyData  $company  The company data
     * @return array<string, string> Formatted company information
     */
    public static function formatCompanyForDisplay(CompanyData $company): array
    {
        return [
            'IC' => $company->ic,
            'Name' => $company->name,
            'DIC' => $company->dic ?? 'N/A',
            'Status' => self::isCompanyActive($company) ? 'Active' : 'Inactive',
            'Legal Form' => self::getLegalForm($company),
            'Establishment Date' => self::getEstablishmentDate($company),
            'Address' => self::getFullAddress($company),
            'Financial Office' => $company->registration->financialOffice ?? 'N/A',
            'Primary Source' => $company->registration->primarySource ?? 'N/A',
        ];
    }

    /**
     * Search companies by name in an array.
     *
     * @param  array<CompanyData>  $companies  Array of companies
     * @param  string  $searchTerm  The search term
     * @param  bool  $caseSensitive  Whether the search should be case sensitive
     * @return array<CompanyData> Companies matching the search term
     */
    public static function searchByName(array $companies, string $searchTerm, bool $caseSensitive = false): array
    {
        $term = $caseSensitive ? $searchTerm : strtolower($searchTerm);

        return array_values(array_filter($companies, function (CompanyData $company) use ($term, $caseSensitive) {
            $name = $caseSensitive ? $company->name : strtolower($company->name);

            return str_contains($name, $term);
        }));
    }

    /**
     * Resolve the configured ARES client instance.
     */
    public static function client(): AresClientInterface
    {
        return app(AresClientInterface::class);
    }

    /**
     * Find company and check if it's active in one call.
     *
     * @param  string  $ic  The company identification number
     * @return bool True if company exists and is active, false otherwise
     */
    public static function isCompanyActiveByIc(string $ic): bool
    {
        $company = Ares::findCompany($ic);

        return $company !== null && self::isCompanyActive($company);
    }

    /**
     * Find company and get its formatted address in one call.
     *
     * @param  string  $ic  The company identification number
     * @return string The formatted address or 'N/A' if not found
     */
    public static function getAddressByIc(string $ic): string
    {
        $company = Ares::findCompany($ic);

        return $company !== null ? self::getFullAddress($company) : 'N/A';
    }

    /**
     * Find company and get its legal form in one call.
     *
     * @param  string  $ic  The company identification number
     * @return string The legal form or 'N/A' if not found
     */
    public static function getLegalFormByIc(string $ic): string
    {
        $company = Ares::findCompany($ic);

        return $company !== null ? self::getLegalForm($company) : 'N/A';
    }

    /**
     * Find company and check if it has VAT number in one call.
     *
     * @param  string  $ic  The company identification number
     * @return bool True if company exists and has VAT number, false otherwise
     */
    public static function hasVatNumberByIc(string $ic): bool
    {
        $company = Ares::findCompany($ic);

        return $company !== null && self::hasVatNumber($company);
    }

    /**
     * Find company and get its establishment date in one call.
     *
     * @param  string  $ic  The company identification number
     * @param  string  $format  The date format (default: Y-m-d)
     * @return string The formatted date or 'N/A' if not found
     */
    public static function getEstablishmentDateByIc(string $ic, string $format = 'Y-m-d'): string
    {
        $company = Ares::findCompany($ic);

        return $company !== null ? self::getEstablishmentDate($company, $format) : 'N/A';
    }

    /**
     * Find company and format it for display in one call.
     *
     * @param  string  $ic  The company identification number
     * @return array<string, string> Formatted company information or empty array if not found
     */
    public static function formatCompanyByIc(string $ic): array
    {
        $company = Ares::findCompany($ic);

        return $company !== null ? self::formatCompanyForDisplay($company) : [];
    }

    /**
     * Validate IC format without making API call.
     *
     * @param  string  $ic  The identification number to validate
     * @return bool True if IC format is valid, false otherwise
     */
    public static function validateIcFormat(string $ic): bool
    {
        return Ares::isValidIc($ic);
    }

    /**
     * Normalize IC format without making API call.
     *
     * @param  string  $ic  The identification number to normalize
     * @return string The normalized 8-digit identification number
     */
    public static function normalizeIcFormat(string $ic): string
    {
        return Ares::normalizeIc($ic);
    }

    /**
     * Create a new fluent API builder instance.
     */
    public static function fluent(): AresFluentBuilder
    {
        return new AresFluentBuilder(self::client());
    }

    /**
     * Create a new fluent API builder instance with custom client.
     *
     * @param  AresClientInterface  $client  The ARES client
     */
    public static function fluentWithClient(AresClientInterface $client): AresFluentBuilder
    {
        return new AresFluentBuilder($client);
    }
}
