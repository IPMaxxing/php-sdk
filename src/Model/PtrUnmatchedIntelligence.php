<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrUnmatchedIntelligence implements PtrIntelligence
{
    public function __construct(
        public string $hostname,
        public string $status,
    ) {}
}
