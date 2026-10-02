<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class IntelligenceData
{
    public function __construct(
        public IntelligenceAs $as,
        public LiveIntelligence $intelligence,
        public string $ip,
        public NetworkClassification $networkClass,
        public bool $isBogon,
        public ?bool $isAnycast,
        public ?bool $isMobile,
        public ?bool $isHosting,
        public ?bool $isSatellite,
        public ?NetworkInfo $network = null,
        public ?MobileCarrier $mobile = null,
        public ?ThreatInfo $threat = null,
        public ?RpkiInfo $rpki = null,
        public ?Company $company = null,
        public ?AbuseContact $abuse = null,
        public ?DnsRecords $dns = null,
    ) {}
}
