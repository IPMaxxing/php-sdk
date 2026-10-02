<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class RpkiInfo
{
    public function __construct(
        public string $status,
        public ?string $source = null,
        public ?string $routeSource = null,
        public ?string $originAsn = null,
        public ?string $routePrefix = null,
        public ?string $roaPrefix = null,
        public ?string $roaAsn = null,
        public ?int $maxLength = null,
        public ?string $ta = null,
        public ?RpkiRtr $rtr = null,
        public ?RpkiValidator $validator = null,
    ) {}
}
