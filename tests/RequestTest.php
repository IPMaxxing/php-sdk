<?php

declare(strict_types=1);

namespace IPMax\Tests;

final class RequestTest extends TestCase
{
    private const UUID_V4 = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    protected function tearDown(): void
    {
        putenv('IPMAX_API_KEY');
    }

    public function testGeoIpRequest(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'));

        $this->client()->geoip(' 8.8.8.8 ');

        $request = $this->request();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.ipm.ax/api/v1/geoip', (string) $request->getUri());
        self::assertSame('Bearer sg_live_test', $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('ipmax-php/0.1.0', $request->getHeaderLine('User-Agent'));
        self::assertMatchesRegularExpression(self::UUID_V4, $request->getHeaderLine('Idempotency-Key'));
        self::assertSame('{"ip":"8.8.8.8"}', (string) $request->getBody());
    }

    public function testIntelligenceRequest(): void
    {
        $this->queue(self::fixture('intelligence-8-8-8-8'));

        $this->client()->intelligence('8.8.8.8');

        $request = $this->request();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.ipm.ax/api/v1/intelligence', (string) $request->getUri());
        self::assertMatchesRegularExpression(self::UUID_V4, $request->getHeaderLine('Idempotency-Key'));
        self::assertSame('{"ip":"8.8.8.8"}', (string) $request->getBody());
    }

    public function testEachLookupGetsAFreshIdempotencyKey(): void
    {
        $this->queue(self::fixture('geoip-8-8-8-8'), self::fixture('geoip-10-1-2-3'));
        $client = $this->client();

        $client->geoip('8.8.8.8');
        $client->geoip('10.1.2.3');

        self::assertNotSame($this->request(0)->getHeaderLine('Idempotency-Key'), $this->request(1)->getHeaderLine('Idempotency-Key'));
    }

    public function testExplicitIdempotencyKeyIsSent(): void
    {
        $this->queue(self::fixture('intelligence-8-8-8-8'));

        $this->client()->intelligence('8.8.8.8', idempotencyKey: 'order-2026-0001-lookup');

        self::assertSame('order-2026-0001-lookup', $this->request()->getHeaderLine('Idempotency-Key'));
    }

    public function testCatalogWorksWithoutAKey(): void
    {
        $this->queue(self::fixture('catalog'));

        $this->client(apiKey: null)->catalog();

        $request = $this->request();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.ipm.ax/api/v1/catalog', (string) $request->getUri());
        self::assertFalse($request->hasHeader('Authorization'));
        self::assertFalse($request->hasHeader('Idempotency-Key'));
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testAccountRequest(): void
    {
        $this->queue(self::fixture('account'));

        $this->client(baseUrl: 'https://proxy.example/')->account();

        $request = $this->request();
        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://proxy.example/api/v1/account', (string) $request->getUri());
        self::assertSame('Bearer sg_live_test', $request->getHeaderLine('Authorization'));
    }

    public function testApiKeyFallsBackToEnvironment(): void
    {
        putenv('IPMAX_API_KEY=sg_live_from_env');
        $this->queue(self::fixture('account'));

        $this->client(apiKey: null)->account();

        self::assertSame('Bearer sg_live_from_env', $this->request()->getHeaderLine('Authorization'));
    }
}
