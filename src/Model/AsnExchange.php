<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class AsnExchange
{
    /** @param list<AsnExchangePort> $ports */
    public function __construct(
        public string $name,
        public string $type,
        #[ListOf(AsnExchangePort::class)]
        public array $ports,
        public ?string $countryCode = null,
    ) {}
}
