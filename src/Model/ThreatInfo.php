<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class ThreatInfo
{
    /**
     * @param list<Blocklist> $blocklists
     * @param list<string>|null $anonymizerExemptions
     */
    public function __construct(
        public ?bool $isTor,
        public ?bool $isVpn,
        public ?bool $isIcloudRelay,
        public ?bool $isCloudflareWarp,
        public ?bool $isProxy,
        public ?bool $isDatacenter,
        public ?bool $isAnonymous,
        public ?bool $isKnownAttacker,
        public ?bool $isKnownAbuser,
        public ?bool $isThreat,
        public bool $isBogon,
        #[ListOf(Blocklist::class)]
        public array $blocklists,
        public int $blocklistCount,
        public ThreatScores $scores,
        #[ListOf('string')]
        public ?array $anonymizerExemptions = null,
    ) {}
}
