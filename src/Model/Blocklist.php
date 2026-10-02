<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Blocklist
{
    public function __construct(
        public string $name,
        public string $site,
        public string $type,
    ) {}
}
