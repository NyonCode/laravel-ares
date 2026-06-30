<?php

declare(strict_types=1);

namespace NyonCode\Ares\Data;

use JsonSerializable;

final class SubjectData implements JsonSerializable
{
    public function __construct(
        public readonly string $ic,
        public readonly string $name,
        public readonly ?string $city,
    ) {}

    /**
     * @return array{ic: string, name: string, city: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'ic' => $this->ic,
            'name' => $this->name,
            'city' => $this->city,
        ];
    }
}
