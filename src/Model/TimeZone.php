<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class TimeZone
{
    public function __construct(
        public string $name,
        public string $abbr,
        public string $offset,
        public bool $isDst,
        public string $currentTime,
    ) {}
}
