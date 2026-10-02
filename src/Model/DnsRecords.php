<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class DnsRecords
{
    /**
     * @param list<string> $ptr
     * @param list<DnsPtrEvidence> $ptrEvidence
     * @param list<DnsHostnameEvidence> $hostnames
     * @param list<string> $sources
     */
    public function __construct(
        #[ListOf('string')]
        public array $ptr,
        #[ListOf(DnsPtrEvidence::class)]
        public array $ptrEvidence,
        #[ListOf(DnsHostnameEvidence::class)]
        public array $hostnames,
        #[ListOf('string')]
        public array $sources,
        public ?string $snapshotId,
        public ?string $updatedAt,
    ) {}
}
