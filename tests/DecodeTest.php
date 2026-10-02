<?php

declare(strict_types=1);

namespace IPMax\Tests;

use GuzzleHttp\Psr7\Response;
use IPMax\Client;
use IPMax\Exception\DecodeException;
use IPMax\Model\NetworkClass;
use IPMax\Model\Product;
use IPMax\Model\PtrAsnMismatchIntelligence;
use IPMax\Model\PtrLocatedIntelligence;
use IPMax\Model\PtrOtherIntelligence;
use IPMax\Model\PtrUnmatchedIntelligence;
use IPMax\Model\RpkiStatus;

final class DecodeTest extends TestCase
{
    public function testCatalog(): void
    {
        $this->queue(self::fixture('catalog'));

        $catalog = $this->client(apiKey: null)->catalog();

        self::assertSame('CNY', $catalog->currency);
        self::assertTrue($catalog->available);
        self::assertSame('sales@ipmax.example', $catalog->purchaseEmail);
        self::assertSame(Product::GEOIP, $catalog->prices[0]->product);
        self::assertSame(437.5, $catalog->prices[0]->unitMicros);
        self::assertSame('威胁与网络情报', $catalog->prices[1]->name);
        self::assertSame(1920.0, $catalog->prices[1]->unitMicros);
    }

    public function testAccount(): void
    {
        $this->queue(self::fixture('account'));

        $account = $this->client()->account();

        self::assertSame('00000000-0000-4000-8000-000000000042', $account->id);
        self::assertSame('ipmax_test', $account->keyPrefix);
        self::assertSame(Product::INTELLIGENCE, $account->wallets[1]->product);
        self::assertSame(1000000.0, $account->wallets[0]->balanceMicros);
        self::assertSame([], $account->entries);
    }

    public function testAccountWithFractionalMicros(): void
    {
        $this->queue(self::fixture(
            'account',
            ...[
                '"balanceMicros": 1000000' => '"balanceMicros": 999562.5',
                '"entries": []' => '"entries": [{"id": "e1", "product": "geoip", "kind": "charge", "amountMicros": 437.5, "balanceMicros": 999562.5, "reference": "receipt-1", "createdAt": "2026-09-29T08:30:00.000Z"}]',
            ],
        ));

        $account = $this->client()->account();

        self::assertSame(999562.5, $account->wallets[0]->balanceMicros);
        self::assertSame('charge', $account->entries[0]->kind);
        self::assertSame(437.5, $account->entries[0]->amountMicros);
        self::assertSame('2026-09-29T08:30:00.000Z', $account->entries[0]->createdAt);
    }

    public function testGeoIpWithCountryEnrichment(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'));

        $data = $this->client()->geoip('8.8.8.8');

        self::assertSame('8.8.8.8', $data->ip);
        self::assertSame('US', $data->geo->countryCode);
        self::assertSame('美国', $data->geo->countryZh);
        self::assertNull($data->geo->countryEn);
        self::assertSame(37.4056, $data->geo->latitude);
        self::assertSame(25.0, $data->geo->radius);
        self::assertSame(24, $data->prefixLength);
        self::assertSame('USD', $data->currency?->code);
        self::assertSame('PDT', $data->timeZone?->abbr);
        self::assertTrue($data->timeZone->isDst);
        self::assertSame('1', $data->callingCode);
        self::assertSame('AS15169', $data->as->asn);
        self::assertNull($data->as->countryCode);
    }

    public function testGeoIpForBogon(): void
    {
        $this->queue(self::fixture('geoip-10-1-2-3'));

        $data = $this->client()->geoip('10.1.2.3');

        self::assertTrue($data->isBogon);
        self::assertNull($data->currency);
        self::assertNull($data->timeZone);
        self::assertNull($data->callingCode);
        self::assertSame('', $data->as->asn);
        self::assertSame('NA', $data->geo->continentCode);
    }

    public function testGeoIpForIpv6(): void
    {
        $this->queue(self::fixture('geoip-240e-390-a1-3cd0-be24-11ff-fe46-aca3'));

        $data = $this->client()->geoip('240e:390:a1:3cd0:be24:11ff:fe46:aca3');

        self::assertSame('240e:390::/32', $data->netmask);
        self::assertSame('CNY', $data->currency?->code);
        self::assertSame('GMT+8', $data->timeZone?->abbr);
        self::assertSame('广州', $data->geo->city);
    }

    public function testRichIntelligence(): void
    {
        $this->queue(self::fixture('intelligence-rich'));

        $data = $this->client()->intelligence('8.8.8.8');

        [$located, $mismatch, $unmatched] = $data->intelligence->ptr;
        self::assertInstanceOf(PtrLocatedIntelligence::class, $located);
        self::assertSame('Tokyo', $located->location->city);
        self::assertSame('13', $located->location->regionCode);
        self::assertSame('metro', $located->location->precision);
        self::assertSame('clli', $located->hint->kind);
        self::assertSame(0.92, $located->confidence->score);
        self::assertSame('geonames', $located->evidence->sources[0]->dataset);
        self::assertInstanceOf(PtrAsnMismatchIntelligence::class, $mismatch);
        self::assertSame('AS64501', $mismatch->observedAsn);
        self::assertSame([], $mismatch->evidence->sources);
        self::assertInstanceOf(PtrUnmatchedIntelligence::class, $unmatched);
        self::assertSame('dns.google', $unmatched->hostname);

        $transfers = $data->intelligence->transfers;
        self::assertNotNull($transfers);
        self::assertSame('Level 3', $transfers[0]->sourceHolder);
        self::assertSame('Google LLC', $transfers[0]->recipientHolder);
        self::assertSame([53, 443], $data->intelligence->segmentProbe?->ports);
        self::assertSame('8.8.8.1', $data->intelligence->segmentProbe->ip);
        self::assertSame(20, $data->intelligence->abuse?->categories[0]->count);
        self::assertSame('2026-09-28T11:00:00.000Z', $data->intelligence->abuse->lastReportedAt);
        self::assertSame('1.25.3', $data->intelligence->cpes[0]->version);
        self::assertSame(['public_dns_resolver', 'web_server'], $data->intelligence->tags);
        self::assertSame(['home_isp'], $data->intelligence->revokedTags);

        $connectivity = $data->as->connectivity;
        self::assertNotNull($connectivity);
        self::assertCount(30, $connectivity->exchanges);
        self::assertSame(3000.0, $connectivity->estimatedCapacity->lowerGbps);
        self::assertNull($connectivity->estimatedCapacity->upperGbps);
        self::assertNull($connectivity->exchangesTotal);
        self::assertSame('DE', $connectivity->exchanges[1]->countryCode);
        self::assertTrue($connectivity->exchanges[0]->ports[0]->rsPeer);

        self::assertSame(RpkiStatus::VALID, $data->rpki?->status);
        self::assertSame(1790000000, $data->rpki->rtr?->lastUpdated);
        self::assertNull($data->rpki->validator);
        self::assertSame(NetworkClass::HOSTING, $data->networkClass->primary);
        self::assertSame('prefix', $data->networkClass->evidence[0]->scope);
        self::assertSame(35.0, $data->threat?->scores->threatScore);
        self::assertSame('known_abuser', $data->threat->blocklists[0]->type);
        self::assertNull($data->threat->anonymizerExemptions);
    }

    public function testSparseIntelligence(): void
    {
        $this->queue(self::fixture('intelligence-10-1-2-3'));

        $data = $this->client()->intelligence('10.1.2.3');

        self::assertNull($data->network);
        self::assertNull($data->rpki);
        self::assertNull($data->dns);
        self::assertNull($data->intelligence->usageType);
        self::assertNull($data->intelligence->transfers);
        self::assertNull($data->intelligence->segmentProbe);
        $threat = $data->threat;
        self::assertNotNull($threat);
        self::assertNull($threat->isTor);
        self::assertTrue($threat->isBogon);
        self::assertNull($threat->scores->trustScore);
        self::assertFalse($data->isAnycast);
        self::assertSame(['anycast_network', 'cdn'], $data->as->tags);
    }

    public function testEveryLookupFixtureDecodes(): void
    {
        $client = $this->client(cacheSize: 0);

        foreach (['8.8.8.8', '10.1.2.3', '240e:390:a1:3cd0:be24:11ff:fe46:aca3'] as $ip) {
            $name = strtr($ip, '.:', '--');
            $this->queue(self::fixture("geoip-{$name}"), self::fixture("intelligence-{$name}"));

            self::assertSame($ip, $client->geoip($ip)->ip);
            self::assertSame($ip, $client->intelligence($ip)->ip);
        }
    }

    public function testUnknownEnumValuesAndFields(): void
    {
        $this->queue(self::fixture(
            'intelligence-rich',
            ...[
                '"primary": "hosting"' => '"primary": "quantum"',
                '"status": "valid"' => '"status": "quantum_safe"',
                '"status": "unmatched"' => '"status": "teleported", "teleporter": {"id": 7}',
                '"kind": "clli"' => '"kind": "geohash"',
                '"dataset": "geonames"' => '"dataset": "osm"',
                '"part": "application"' => '"part": "firmware"',
                '"web_server"' => '"carrier_grade_nat"',
                '"usage_type": "DCH",' => '"usage_type": "DCH", "future_signal": [1, 2, 3],',
                '"ip": "8.8.8.8",' => '"ip": "8.8.8.8", "brand_new": {"nested": true},',
            ],
        ));

        $data = $this->client()->intelligence('8.8.8.8');

        self::assertSame('quantum', $data->networkClass->primary);
        self::assertSame('quantum_safe', $data->rpki?->status);
        self::assertSame('carrier_grade_nat', $data->intelligence->tags[1]);
        $located = $data->intelligence->ptr[0];
        self::assertInstanceOf(PtrLocatedIntelligence::class, $located);
        self::assertSame('geohash', $located->hint->kind);
        self::assertSame('osm', $located->evidence->sources[0]->dataset);
        self::assertSame('firmware', $data->intelligence->cpes[0]->part);
        $other = $data->intelligence->ptr[2];
        self::assertInstanceOf(PtrOtherIntelligence::class, $other);
        self::assertSame('teleported', $other->status);
        self::assertSame('dns.google', $other->hostname);
    }

    public function testMalformedSuccessBodyFails(): void
    {
        $this->queue(new Response(200, body: '{"status": true, "data": {"currency": "CNY"}}'));

        $this->expectException(DecodeException::class);
        $this->expectExceptionMessage('Missing field');

        $this->client()->catalog();
    }

    public function testClientIsConstructibleWithDiscoveredDefaults(): void
    {
        self::assertInstanceOf(Client::class, new Client('sg_live_test'));
    }
}
