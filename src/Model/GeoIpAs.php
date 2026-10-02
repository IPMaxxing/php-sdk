<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class GeoIpAs
{
    public function __construct(
        public string $asn,
        public string $name,
        public string $domain,
        public ?string $nameEn = null,
        public ?string $countryCode = null,
    ) {}
}
