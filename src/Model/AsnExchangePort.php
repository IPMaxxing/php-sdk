<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class AsnExchangePort
{
    public function __construct(
        public float $capacityGbps,
        public ?bool $operational = null,
        public ?bool $rsPeer = null,
    ) {}
}
