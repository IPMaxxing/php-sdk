<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class SegmentProbe
{
    /**
     * @param list<int> $ports
     * @param list<string> $tags
     */
    public function __construct(
        public string $ip,
        #[ListOf('int')]
        public array $ports,
        #[ListOf('string')]
        public array $tags,
    ) {}
}
