<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class AsnEstimatedCapacity
{
    public function __construct(
        public float $lowerGbps,
        public string $label,
        public ?float $upperGbps = null,
    ) {}
}
