<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class CpeObservation
{
    public function __construct(
        public string $cpe,
        public string $part,
        public string $vendor,
        public string $product,
        public ?string $version,
    ) {}
}
