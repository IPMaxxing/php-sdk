<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class AsnConnectivity
{
    /** @param list<AsnExchange> $exchanges */
    public function __construct(
        public string $declaredTraffic,
        public AsnEstimatedCapacity $estimatedCapacity,
        #[ListOf(AsnExchange::class)]
        public array $exchanges,
        public ?int $exchangesTotal = null,
    ) {}
}
