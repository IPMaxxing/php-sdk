<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class DnsHostnameEvidence
{
    /**
     * @param list<string> $kinds
     * @param list<string> $sources
     */
    public function __construct(
        public string $hostname,
        #[ListOf('string')]
        public array $kinds,
        #[ListOf('string')]
        public array $sources,
        public bool $forwardConfirmed,
        public ?string $firstSeen,
        public ?string $lastSeen,
    ) {}
}
