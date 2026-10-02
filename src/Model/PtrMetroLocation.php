<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class PtrMetroLocation
{
    public function __construct(
        public string $city,
        public ?string $region,
        public ?string $regionCode,
        public string $country,
        public string $countryCode,
        public float $latitude,
        public float $longitude,
        public string $precision,
    ) {}
}
