<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrEvidenceSource
{
    public function __construct(
        public string $dataset,
        public string $version,
    ) {}
}
