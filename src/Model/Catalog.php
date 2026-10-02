<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class Catalog
{
    /** @param list<Price> $prices */
    public function __construct(
        public string $currency,
        #[ListOf(Price::class)]
        public array $prices,
        public string $purchaseEmail,
        public bool $available,
    ) {}
}
