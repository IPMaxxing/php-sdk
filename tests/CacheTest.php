<?php

declare(strict_types=1);

namespace IPMax\Tests;

use IPMax\Exception\NotFoundException;

final class CacheTest extends TestCase
{
    public function testRepeatedLookupHitsTheServerOnce(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'));
        $client = $this->client();

        $first = $client->geoip('8.8.8.8');
        $second = $client->geoip(' 8.8.8.8 ');

        self::assertSame($first, $second);
        self::assertSame(1, $this->requestCount());
    }

    public function testProductsAreCachedSeparately(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'), self::fixture('intelligence-8-8-8-8'));
        $client = $this->client();

        $client->geoip('8.8.8.8');
        $client->intelligence('8.8.8.8');
        $client->geoip('8.8.8.8');
        $client->intelligence('8.8.8.8');

        self::assertSame(2, $this->requestCount());
    }

    public function testExpiredEntriesAreRefetched(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'), self::fixture('geoip-8-8-8-8'));
        $client = $this->client(cacheTtl: 1);

        $client->geoip('8.8.8.8');
        $client->geoip('8.8.8.8');
        usleep(1_050_000);
        $client->geoip('8.8.8.8');

        self::assertSame(2, $this->requestCount());
    }

    public function testLeastRecentlyUsedEntryIsEvicted(): void
    {
        $this->queue(
            self::fixture('geoip-8-8-8-8'),
            self::fixture('geoip-10-1-2-3'),
            self::fixture('geoip-240e-390-a1-3cd0-be24-11ff-fe46-aca3'),
            self::fixture('geoip-10-1-2-3'),
        );
        $client = $this->client(cacheSize: 2);

        $client->geoip('8.8.8.8');
        $client->geoip('10.1.2.3');
        $client->geoip('8.8.8.8');
        $client->geoip('240e:390:a1:3cd0:be24:11ff:fe46:aca3');
        $client->geoip('8.8.8.8');
        $client->geoip('10.1.2.3');

        self::assertSame(4, $this->requestCount());
        self::assertStringContainsString('10.1.2.3', (string) $this->request(3)->getBody());
    }

    public function testZeroSizeDisablesTheCache(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'), self::fixture('geoip-8-8-8-8'));
        $client = $this->client(cacheSize: 0);

        $client->geoip('8.8.8.8');
        $client->geoip('8.8.8.8');

        self::assertSame(2, $this->requestCount());
    }

    public function testErrorsAreNotCached(): void
    {
        $this->queue(self::fixture('error-not-found'), self::fixture('geoip-8-8-8-8'));
        $client = $this->client();

        try {
            $client->geoip('8.8.8.8');
            self::fail('Expected the first lookup to fail');
        } catch (NotFoundException) {
        }
        $client->geoip('8.8.8.8');

        self::assertSame(2, $this->requestCount());
    }

    public function testExplicitIdempotencyKeyBypassesTheReadButStoresTheResult(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'), self::fixture('geoip-8-8-8-8'));
        $client = $this->client();

        $client->geoip('8.8.8.8');
        $fresh = $client->geoip('8.8.8.8', idempotencyKey: 'caller-chosen-key-0001');
        $cached = $client->geoip('8.8.8.8');

        self::assertSame(2, $this->requestCount());
        self::assertSame($fresh, $cached);
    }

    public function testCatalogAndAccountAreNeverCached(): void
    {
        $this->queue(self::fixture('catalog'), self::fixture('catalog'), self::fixture('account'), self::fixture('account'));
        $client = $this->client();

        $client->catalog();
        $client->catalog();
        $client->account();
        $client->account();

        self::assertSame(4, $this->requestCount());
    }
}
