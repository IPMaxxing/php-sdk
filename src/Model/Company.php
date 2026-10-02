<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Company
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $domain,
        public ?string $phone,
        public ?string $type,
        public ?string $network,
        public ?string $country,
        public ?string $source = null,
    ) {}
}
