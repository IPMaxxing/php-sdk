<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class IntelligenceResponse
{
    public function __construct(
        public bool $status,
        public IntelligenceData $data,
    ) {}
}
