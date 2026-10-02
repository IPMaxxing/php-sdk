<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Wallet
{
    public function __construct(
        public string $product,
        public float $balanceMicros,
    ) {}
}
