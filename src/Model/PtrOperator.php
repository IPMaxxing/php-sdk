<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrOperator
{
    public function __construct(
        public string $name,
        public string $asn,
        public string $suffix,
    ) {}
}
