<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class Geolocation
{
    public function __construct(
        public string $city,
        public string $region,
        public string $district,
        public string $regionCode,
        public string $adcode,
        public string $country,
        public string $countryCode,
        public string $continent,
        public string $continentCode,
        public ?float $latitude,
        public ?float $longitude,
        public string $timezone,
        public string $postalCode,
        public ?float $radius,
        public ?string $areaCode = null,
        public ?string $plusCode = null,
        public ?string $countryEn = null,
        public ?string $countryZh = null,
        public ?string $regionEn = null,
        public ?string $regionZh = null,
        public ?string $cityEn = null,
        public ?string $cityZh = null,
        public ?string $districtEn = null,
        public ?string $districtZh = null,
    ) {}
}
