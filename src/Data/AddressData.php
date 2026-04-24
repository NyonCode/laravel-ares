<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

final class AddressData
{
    public function __construct(
        public readonly ?string $formatted,
        public readonly ?string $street,
        public readonly ?string $houseNumber,
        public readonly ?string $district,
        public readonly ?string $city,
        public readonly ?string $postalCode,
        public readonly ?string $countryCode,
        public readonly ?string $country,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): ?self
    {
        $street = self::nullableString($data['nazevUlice'] ?? null);
        $houseNumber = self::resolveHouseNumber($data);
        $district = self::nullableString($data['nazevCastiObce'] ?? null);
        $city = self::nullableString($data['nazevObce'] ?? $data['obec'] ?? null);
        $postalCode = self::scalarToString($data['psc'] ?? null);
        $countryCode = self::nullableString($data['kodStatu'] ?? null);
        $country = self::nullableString($data['nazevStatu'] ?? null);
        $formatted = self::resolveFormattedAddress($data, $street, $houseNumber, $district, $city, $postalCode);

        if (
            $formatted === null
            && $street === null
            && $houseNumber === null
            && $district === null
            && $city === null
            && $postalCode === null
            && $countryCode === null
            && $country === null
        ) {
            return null;
        }

        return new self(
            formatted: $formatted,
            street: $street,
            houseNumber: $houseNumber,
            district: $district,
            city: $city,
            postalCode: $postalCode,
            countryCode: $countryCode,
            country: $country,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveFormattedAddress(
        array $data,
        ?string $street,
        ?string $houseNumber,
        ?string $district,
        ?string $city,
        ?string $postalCode,
    ): ?string {
        $textAddress = self::nullableString($data['textovaAdresa'] ?? null);

        if ($textAddress !== null) {
            return $textAddress;
        }

        $addressParts = array_values(array_filter([
            self::combineStreetAndHouseNumber($street, $houseNumber),
            $district,
            $postalCode !== null && $city !== null ? "{$postalCode} {$city}" : $city,
            $postalCode !== null && $city === null ? $postalCode : null,
        ]));

        if ($addressParts === []) {
            return null;
        }

        return implode(', ', $addressParts);
    }

    private static function combineStreetAndHouseNumber(?string $street, ?string $houseNumber): ?string
    {
        if ($street === null && $houseNumber === null) {
            return null;
        }

        if ($street !== null && $houseNumber !== null) {
            return "{$street} {$houseNumber}";
        }

        return $street ?? $houseNumber;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveHouseNumber(array $data): ?string
    {
        $descriptiveNumber = self::scalarToString($data['cisloDomovni'] ?? null);
        $orientationNumber = self::scalarToString($data['cisloOrientacni'] ?? null);
        $orientationLetter = self::nullableString($data['cisloOrientacniPismeno'] ?? null) ?? '';

        if ($descriptiveNumber === null && $orientationNumber === null) {
            return null;
        }

        if ($descriptiveNumber !== null && $orientationNumber !== null) {
            return $descriptiveNumber.'/'.$orientationNumber.$orientationLetter;
        }

        return $descriptiveNumber ?? $orientationNumber.$orientationLetter;
    }

    private static function nullableString(mixed $value): ?string
    {
        $string = self::scalarToString($value);

        return $string === null || $string === '' ? null : $string;
    }

    private static function scalarToString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return trim((string) $value);
    }
}
