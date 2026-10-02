<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrConfidence
{
    public function __construct(
        public string $level,
        public float $score,
    ) {}
}
