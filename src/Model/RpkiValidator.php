<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class RpkiValidator
{
    public function __construct(
        public string $source,
        public string $engine,
        public int $lastUpdated,
    ) {}
}
