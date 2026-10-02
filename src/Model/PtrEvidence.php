<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class PtrEvidence
{
    /** @param list<PtrEvidenceSource> $sources */
    public function __construct(
        public string $ruleId,
        public string $rulesetVersion,
        #[ListOf(PtrEvidenceSource::class)]
        public array $sources,
    ) {}
}
