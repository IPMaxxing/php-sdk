<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class LookupRequest
{
    public function __construct(
        public string $ip,
    ) {}
}
