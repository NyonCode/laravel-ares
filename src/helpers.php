<?php

declare(strict_types=1);

use NyonCode\Ares\Data\CompanyData;
use NyonCode\Ares\Helpers\AresHelper;

if (! function_exists('ares')) {
    /**
     * Get the ARES client instance or call helper methods.
     *
     * @param  string|null  $method  The helper method to call
     * @param  mixed  ...$args  The arguments to pass to the method
     */
    function ares(?string $method = null, ...$args): mixed
    {
        if ($method === null) {
            return AresHelper::fluent();
        }

        if (method_exists(AresHelper::class, $method)) {
            return AresHelper::{$method}(...$args);
        }

        $client = AresHelper::client();

        if (method_exists($client, $method)) {
            return $client->{$method}(...$args);
        }

        throw new InvalidArgumentException(sprintf('Method [%s] does not exist on AresHelper.', $method));
    }
}

if (! function_exists('ares_is_company_active')) {
    /**
     * Check if a company is active by its IC.
     *
     * @param  string  $ic  The company identification number
     * @return bool True if company exists and is active, false otherwise
     */
    function ares_is_company_active(string $ic): bool
    {
        return AresHelper::isCompanyActiveByIc($ic);
    }
}

if (! function_exists('ares_get_address')) {
    /**
     * Get company address by IC.
     *
     * @param  string  $ic  The company identification number
     * @return string The formatted address or 'N/A' if not found
     */
    function ares_get_address(string $ic): string
    {
        return AresHelper::getAddressByIc($ic);
    }
}

if (! function_exists('ares_has_vat')) {
    /**
     * Check if company has VAT number by IC.
     *
     * @param  string  $ic  The company identification number
     * @return bool True if company exists and has VAT number, false otherwise
     */
    function ares_has_vat(string $ic): bool
    {
        return AresHelper::hasVatNumberByIc($ic);
    }
}

if (! function_exists('ares_get_legal_form')) {
    /**
     * Get company legal form by IC.
     *
     * @param  string  $ic  The company identification number
     * @return string The legal form or 'N/A' if not found
     */
    function ares_get_legal_form(string $ic): string
    {
        return AresHelper::getLegalFormByIc($ic);
    }
}

if (! function_exists('ares_get_establishment_date')) {
    /**
     * Get company establishment date by IC.
     *
     * @param  string  $ic  The company identification number
     * @param  string  $format  The date format
     * @return string The formatted date or 'N/A' if not found
     */
    function ares_get_establishment_date(string $ic, string $format = 'Y-m-d'): string
    {
        return AresHelper::getEstablishmentDateByIc($ic, $format);
    }
}

if (! function_exists('ares_format_company')) {
    /**
     * Get formatted company data by IC.
     *
     * @param  string  $ic  The company identification number
     * @return array<string, string> Formatted company data
     */
    function ares_format_company(string $ic): array
    {
        return AresHelper::formatCompanyByIc($ic);
    }
}

if (! function_exists('ares_get_company_statistics')) {
    /**
     * Get company statistics for a list of IC values.
     *
     * @param  array<string>  $ics  The company identification numbers
     * @return array<string, mixed> Statistics for found companies
     */
    function ares_get_company_statistics(array $ics): array
    {
        $results = AresHelper::validateMultipleIcs($ics);
        $companies = array_values(array_filter(
            $results,
            static fn (mixed $company): bool => $company instanceof CompanyData
        ));

        return AresHelper::getCompanyStatistics($companies);
    }
}

if (! function_exists('ares_validate_ic')) {
    /**
     * Validate IC format.
     *
     * @param  string  $ic  The identification number to validate
     * @return bool True if IC format is valid, false otherwise
     */
    function ares_validate_ic(string $ic): bool
    {
        return AresHelper::validateIcFormat($ic);
    }
}

if (! function_exists('ares_normalize_ic')) {
    /**
     * Normalize IC format.
     *
     * @param  string  $ic  The identification number to normalize
     * @return string The normalized 8-digit identification number
     */
    function ares_normalize_ic(string $ic): string
    {
        return AresHelper::normalizeIcFormat($ic);
    }
}
