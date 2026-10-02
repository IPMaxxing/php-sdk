<?php

declare(strict_types=1);

namespace IPMax\Tests;

use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\TransferException;
use Http\Mock\Client as MockClient;
use IPMax\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected MockClient $http;

    protected function setUp(): void
    {
        putenv('IPMAX_API_KEY');
        $this->http = new MockClient();
        $this->http->setDefaultException(new TransferException('No response queued'));
    }

    protected function client(
        ?string $apiKey = 'sg_live_test',
        string $baseUrl = 'https://api.ipm.ax',
        float $timeout = 10.0,
        int $maxRetries = 2,
        int $cacheSize = 1024,
        int $cacheTtl = 300,
    ): Client {
        return new Client(
            apiKey: $apiKey,
            http: $this->http,
            baseUrl: $baseUrl,
            timeout: $timeout,
            maxRetries: $maxRetries,
            cacheSize: $cacheSize,
            cacheTtl: $cacheTtl,
        );
    }

    protected function queue(ResponseInterface ...$responses): void
    {
        foreach ($responses as $response) {
            $this->http->addResponse($response);
        }
    }

    protected function request(int $index = 0): RequestInterface
    {
        $requests = $this->http->getRequests();
        self::assertArrayHasKey($index, $requests);

        return $requests[$index];
    }

    protected function requestCount(): int
    {
        return count($this->http->getRequests());
    }

    protected static function fixture(string $name, string ...$replacements): ResponseInterface
    {
        $contents = file_get_contents(__DIR__ . "/Fixtures/{$name}.json");
        self::assertIsString($contents);
        $fixture = json_decode(strtr($contents, $replacements), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($fixture);
        self::assertIsInt($fixture['status']);
        self::assertIsArray($fixture['headers']);
        $response = new Response($fixture['status'], body: json_encode($fixture['body'], JSON_THROW_ON_ERROR));
        foreach ($fixture['headers'] as $header => $value) {
            self::assertIsString($header);
            self::assertIsString($value);
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }

    protected static function error(int $status, int $code = 1400, string $retryAfter = ''): ResponseInterface
    {
        $body = json_encode([
            'status' => false,
            'data' => null,
            'error' => ['code' => $code, 'message' => "HTTP {$status}", 'request_id' => "req-{$status}", 'retryable' => $status >= 500],
        ], JSON_THROW_ON_ERROR);

        return new Response($status, $retryAfter === '' ? [] : ['Retry-After' => $retryAfter], $body);
    }
}
