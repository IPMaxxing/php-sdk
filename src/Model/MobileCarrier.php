<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class MobileCarrier
{
    public function __construct(
        public string $mcc,
        public string $mnc,
        public string $brand,
    ) {}
}
