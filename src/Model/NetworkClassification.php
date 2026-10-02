<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class NetworkClassification
{
    /**
     * @param list<string> $conflicts
     * @param list<NetworkClassEvidence> $evidence
     */
    public function __construct(
        public string $primary,
        public string $confidence,
        public string $source,
        #[ListOf('string')]
        public array $conflicts,
        #[ListOf(NetworkClassEvidence::class)]
        public array $evidence,
    ) {}
}
