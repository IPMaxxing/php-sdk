<?php

declare(strict_types=1);

namespace IPMax\Model;

final class AnonymizerExemptionReason
{
    public const CLOUDFLARE_CDN_ORIGIN = 'cloudflare_cdn_origin';
    public const SEARCH_ENGINE_CRAWLER = 'search_engine_crawler';
}
