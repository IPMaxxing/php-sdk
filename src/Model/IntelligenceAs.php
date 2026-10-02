<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class IntelligenceAs
{
    /** @param list<string>|null $tags */
    public function __construct(
        public string $asn,
        public string $name,
        public string $domain,
        public string $type,
        public ?string $nameEn = null,
        public ?string $countryCode = null,
        public ?string $class = null,
        #[ListOf('string')]
        public ?array $tags = null,
        public ?AsnConnectivity $connectivity = null,
    ) {}
}
