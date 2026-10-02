<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Price
{
    public function __construct(
        public string $product,
        public string $name,
        public float $unitMicros,
    ) {}
}
