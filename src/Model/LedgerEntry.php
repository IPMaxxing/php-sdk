<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class LedgerEntry
{
    public function __construct(
        public string $id,
        public string $product,
        public string $kind,
        public float $amountMicros,
        public float $balanceMicros,
        public string $reference,
        public string $createdAt,
    ) {}
}
