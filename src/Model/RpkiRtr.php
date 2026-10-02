<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class RpkiRtr
{
    public function __construct(
        public string $source,
        public int $lastUpdated,
        public ?string $server = null,
    ) {}
}
