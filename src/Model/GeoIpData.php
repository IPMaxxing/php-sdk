<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class GeoIpData
{
    public function __construct(
        public GeoIpAs $as,
        public string $ip,
        public Geolocation $geo,
        public string $netmask,
        public ?int $prefixLength,
        public bool $isBogon,
        public ?Currency $currency = null,
        public ?TimeZone $timeZone = null,
        public ?string $callingCode = null,
    ) {}
}
