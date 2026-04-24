<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

use NyonCode\Ares\Exceptions\InvalidApiResponseException;

final class CompanyData
{
    /**
     * @param  array<string, mixed>  $rawData
     */
    public function __construct(
        public readonly string $ic,
        public readonly string $name,
        public readonly ?string $dic,
        public readonly ?string $dicSkDph,
        public readonly ?AddressData $registeredOffice,
        public readonly ?DeliveryAddressData $deliveryAddress,
        public readonly RegistrationData $registration,
        public readonly array $rawData,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidApiResponseException
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            ic: self::requiredString($data, 'ico'),
            name: self::requiredString($data, 'obchodniJmeno'),
            dic: self::nullableString($data['dic'] ?? null),
            dicSkDph: self::nullableString($data['dicSkDph'] ?? null),
            registeredOffice: AddressData::fromApiResponse(self::arrayData($data['sidlo'] ?? null)),
            deliveryAddress: DeliveryAddressData::fromApiResponse(self::arrayData($data['adresaDorucovaci'] ?? null)),
            registration: RegistrationData::fromApiResponse($data),
            rawData: $data,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidApiResponseException
     */
    private static function requiredString(array $data, string $field): string
    {
        $value = self::nullableString($data[$field] ?? null);

        if ($value === null) {
            throw InvalidApiResponseException::missingRequiredField($field);
        }

        return $value;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayData(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
