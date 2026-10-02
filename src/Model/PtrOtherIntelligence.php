<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrOtherIntelligence implements PtrIntelligence
{
    public function __construct(
        public string $hostname,
        public string $status,
    ) {}
}
