<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class ThreatScores
{
    public function __construct(
        public ?float $vpnScore,
        public ?float $proxyScore,
        public ?float $threatScore,
        public ?float $trustScore,
    ) {}
}
