<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrHint
{
    public function __construct(
        public string $value,
        public string $kind,
    ) {}
}
