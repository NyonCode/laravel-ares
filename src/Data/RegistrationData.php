<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

use NyonCode\Ares\Enums\RegistrationSourceState;

final class RegistrationData
{
    /**
     * @param  list<string>  $naceCodes
     * @param  list<string>  $nace2008Codes
     * @param  list<RegistrationStatusData>  $sourceStatuses
     */
    public function __construct(
        public readonly ?string $legalForm,
        public readonly ?string $financialOffice,
        public readonly ?string $dateOfEstablishment,
        public readonly ?string $dateOfLastUpdate,
        public readonly ?string $primarySource,
        public readonly ?string $businessRegisterFileMark,
        public readonly array $naceCodes,
        public readonly array $nace2008Codes,
        public readonly array $sourceStatuses,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            legalForm: self::nullableString($data['pravniForma'] ?? null),
            financialOffice: self::nullableString($data['financniUrad'] ?? null),
            dateOfEstablishment: self::nullableString($data['datumVzniku'] ?? null),
            dateOfLastUpdate: self::nullableString($data['datumAktualizace'] ?? null),
            primarySource: self::nullableString($data['primarniZdroj'] ?? null),
            businessRegisterFileMark: self::resolveBusinessRegisterFileMark(self::listOfAssociativeArrays($data['dalsiUdaje'] ?? null)),
            naceCodes: self::normalizeStringList(self::listData($data['czNace'] ?? null)),
            nace2008Codes: self::normalizeStringList(self::listData($data['czNace2008'] ?? null)),
            sourceStatuses: self::normalizeSourceStatuses(self::associativeArrayData($data['seznamRegistraci'] ?? null)),
        );
    }

    public function sourceStatus(string $source): ?RegistrationStatusData
    {
        foreach ($this->sourceStatuses as $sourceStatus) {
            if ($sourceStatus->source === $source) {
                return $sourceStatus;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $additionalData
     */
    private static function resolveBusinessRegisterFileMark(array $additionalData): ?string
    {
        foreach ($additionalData as $entry) {
            $fileMark = self::nullableString($entry['spisovaZnacka'] ?? null);

            if ($fileMark !== null) {
                return $fileMark;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<string>
     */
    private static function normalizeStringList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $string = self::nullableString($value);

            if ($string !== null) {
                $normalized[] = $string;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $registrationStatuses
     * @return list<RegistrationStatusData>
     */
    private static function normalizeSourceStatuses(array $registrationStatuses): array
    {
        $normalized = [];

        foreach ($registrationStatuses as $key => $value) {
            $rawStatus = self::nullableString($value);

            if ($rawStatus === null) {
                continue;
            }

            $normalized[] = new RegistrationStatusData(
                source: self::resolveSourceName($key),
                rawStatus: $rawStatus,
                status: RegistrationSourceState::tryFromApi($rawStatus),
            );
        }

        return $normalized;
    }

    private static function resolveSourceName(string $key): string
    {
        $prefix = 'stavZdroje';

        if (str_starts_with($key, $prefix)) {
            return lcfirst(substr($key, strlen($prefix)));
        }

        return $key;
    }

    /**
     * @return list<mixed>
     */
    private static function listData(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function associativeArrayData(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function listOfAssociativeArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
