<?php

declare(strict_types=1);

namespace IPMax\Tests;

use GuzzleHttp\Psr7\Request;
use Http\Client\Exception\NetworkException;
use IPMax\Exception\ConnectionException;
use IPMax\Exception\InvalidRequestException;
use IPMax\Exception\ServerException;

final class RetryTest extends TestCase
{
    public function testServerErrorIsRetriedWithTheSameIdempotencyKey(): void
    {
        $this->queue(self::error(503), self::fixture('geoip-8-8-8-8'));

        $data = $this->client()->geoip('8.8.8.8');

        self::assertSame('8.8.8.8', $data->ip);
        self::assertSame(2, $this->requestCount());
        $key = $this->request(0)->getHeaderLine('Idempotency-Key');
        self::assertNotSame('', $key);
        self::assertSame($key, $this->request(1)->getHeaderLine('Idempotency-Key'));
    }

    public function testNetworkErrorIsRetriedWithTheSameIdempotencyKey(): void
    {
        $this->http->addException(new NetworkException('Connection reset', new Request('POST', '/')));
        $this->queue(self::fixture('intelligence-8-8-8-8'));

        $this->client()->intelligence('8.8.8.8', idempotencyKey: 'retry-me-please-0001');

        self::assertSame(2, $this->requestCount());
        self::assertSame('retry-me-please-0001', $this->request(0)->getHeaderLine('Idempotency-Key'));
        self::assertSame('retry-me-please-0001', $this->request(1)->getHeaderLine('Idempotency-Key'));
    }

    public function testRetryAfterIsHonoured(): void
    {
        $this->queue(self::error(429, 1011, '0.2'), self::fixture('catalog'));

        $started = hrtime(true);
        $this->client()->catalog();

        self::assertGreaterThanOrEqual(0.2, (hrtime(true) - $started) / 1e9);
        self::assertSame(2, $this->requestCount());
    }

    public function testRetriesStopAfterMaxRetries(): void
    {
        $this->queue(self::error(503, retryAfter: '0'), self::error(503, retryAfter: '0'), self::error(503, retryAfter: '0'), self::fixture('catalog'));

        $this->expectException(ServerException::class);

        try {
            $this->client(maxRetries: 2)->catalog();
        } finally {
            self::assertSame(3, $this->requestCount());
        }
    }

    public function testNetworkRetriesStopAfterMaxRetries(): void
    {
        $this->http->addException(new NetworkException('Connection reset', new Request('GET', '/')));
        $this->http->addException(new NetworkException('Connection reset', new Request('GET', '/')));

        $this->expectException(ConnectionException::class);

        try {
            $this->client(maxRetries: 1)->catalog();
        } finally {
            self::assertSame(2, $this->requestCount());
        }
    }

    public function testClientErrorsAreNotRetried(): void
    {
        $this->queue(self::fixture('error-invalid-request'), self::fixture('geoip-8-8-8-8'));

        $this->expectException(InvalidRequestException::class);

        try {
            $this->client()->geoip('8.8.8.8');
        } finally {
            self::assertSame(1, $this->requestCount());
        }
    }

    public function testZeroRetriesMakesOneAttempt(): void
    {
        $this->queue(self::error(500), self::fixture('catalog'));

        $this->expectException(ServerException::class);

        try {
            $this->client(maxRetries: 0)->catalog();
        } finally {
            self::assertSame(1, $this->requestCount());
        }
    }
}
