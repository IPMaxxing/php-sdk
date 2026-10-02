<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class NetworkInfo
{
    /** @param list<string> $tags */
    public function __construct(
        public string $connectionType,
        public string $usageType,
        public string $netSpeed,
        #[ListOf('string')]
        public array $tags,
        public ?string $addressType = null,
    ) {}
}
