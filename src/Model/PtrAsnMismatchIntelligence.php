<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrAsnMismatchIntelligence implements PtrIntelligence
{
    public function __construct(
        public string $hostname,
        public string $status,
        public PtrOperator $operator,
        public string $observedAsn,
        public PtrEvidence $evidence,
    ) {}
}
