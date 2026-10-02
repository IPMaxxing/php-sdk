<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class GeoIpResponse
{
    public function __construct(
        public bool $status,
        public GeoIpData $data,
    ) {}
}
