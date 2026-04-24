<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

final class DeliveryAddressData
{
    /**
     * @param  list<string>  $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly ?string $formatted,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApiResponse(array $data): ?self
    {
        $lines = array_values(array_filter([
            self::nullableString($data['radekAdresy1'] ?? null),
            self::nullableString($data['radekAdresy2'] ?? null),
            self::nullableString($data['radekAdresy3'] ?? null),
        ]));

        if ($lines === []) {
            return null;
        }

        return new self(
            lines: $lines,
            formatted: implode(', ', $lines),
        );
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
