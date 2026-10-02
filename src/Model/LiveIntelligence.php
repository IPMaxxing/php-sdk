<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class LiveIntelligence
{
    /**
     * @param list<PtrIntelligence> $ptr
     * @param list<string> $nameservers
     * @param list<CpeObservation> $cpes
     * @param list<string> $tags
     * @param list<string> $revokedTags
     * @param list<ResourceTransfer>|null $transfers
     */
    public function __construct(
        #[ListOf(PtrIntelligence::class)]
        public array $ptr,
        #[ListOf('string')]
        public array $nameservers,
        #[ListOf(CpeObservation::class)]
        public array $cpes,
        #[ListOf('string')]
        public array $tags,
        #[ListOf('string')]
        public array $revokedTags,
        public ?string $usageType,
        public ?RpkiInfo $rpki = null,
        public ?AbuseReports $abuse = null,
        #[ListOf(ResourceTransfer::class)]
        public ?array $transfers = null,
        public ?SegmentProbe $segmentProbe = null,
    ) {}
}
