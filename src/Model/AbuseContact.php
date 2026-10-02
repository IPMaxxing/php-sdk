<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class AbuseContact
{
    public function __construct(
        public ?string $name,
        public ?string $address,
        public ?string $country,
        public ?string $email,
        public ?string $network,
        public ?string $phone,
        public ?string $source = null,
    ) {}
}
