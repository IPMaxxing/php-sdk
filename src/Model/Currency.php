<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Currency
{
    public function __construct(
        public string $name,
        public string $code,
        public string $symbol,
        public string $native,
        public string $plural,
    ) {}
}
