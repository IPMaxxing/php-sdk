<?php

declare(strict_types=1);

namespace IPMax;

use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use IPMax\Exception\ApiException;
use IPMax\Exception\ConnectionException;
use IPMax\Exception\DecodeException;
use IPMax\Exception\TimeoutException;
use IPMax\Internal\Hydrator;
use IPMax\Internal\LruCache;
use IPMax\Model\Account;
use IPMax\Model\AccountResponse;
use IPMax\Model\Catalog;
use IPMax\Model\CatalogResponse;
use IPMax\Model\ErrorResponse;
use IPMax\Model\GeoIpData;
use IPMax\Model\GeoIpResponse;
use IPMax\Model\IntelligenceData;
use IPMax\Model\IntelligenceResponse;
use IPMax\Model\LookupRequest;
use IPMax\Model\Product;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Client
{
    private const VERSION = '0.1.0';
    private const BACKOFF_BASE = 0.5;
    private const BACKOFF_CAP = 8.0;

    private readonly ?string $apiKey;
    private readonly ClientInterface $http;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    private readonly string $baseUrl;
    private readonly LruCache $cache;

    public function __construct(
        ?string $apiKey = null,
        ?ClientInterface $http = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUrl = 'https://api.ipm.ax',
        private readonly float $timeout = 10.0,
        private readonly int $maxRetries = 2,
        int $cacheSize = 1024,
        int $cacheTtl = 300,
    ) {
        $this->apiKey = ($apiKey ?? getenv('IPMAX_API_KEY')) ?: null;
        $this->http = $http ?? (class_exists(GuzzleClient::class) ? new GuzzleClient(['timeout' => $timeout]) : Psr18ClientDiscovery::find());
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cache = new LruCache($cacheSize, $cacheTtl);
    }

    public function catalog(): Catalog
    {
        return $this->send($this->request('GET', '/api/v1/catalog'), CatalogResponse::class)->data;
    }

    public function account(): Account
    {
        return $this->send($this->request('GET', '/api/v1/account'), AccountResponse::class)->data;
    }

    public function geoip(string $ip, ?string $idempotencyKey = null): GeoIpData
    {
        return $this->lookup(Product::GEOIP, $ip, $idempotencyKey, GeoIpResponse::class)->data;
    }

    public function intelligence(string $ip, ?string $idempotencyKey = null): IntelligenceData
    {
        return $this->lookup(Product::INTELLIGENCE, $ip, $idempotencyKey, IntelligenceResponse::class)->data;
    }

    /**
     * @template T of object
     * @param class-string<T> $envelope
     * @return T
     */
    private function lookup(string $product, string $ip, ?string $idempotencyKey, string $envelope): object
    {
        $address = trim($ip);
        $key = "{$product}:{$address}";
        $cached = $idempotencyKey === null ? $this->cache->get($key) : null;
        if ($cached instanceof $envelope) {
            return $cached;
        }
        $body = json_encode(new LookupRequest($address), JSON_THROW_ON_ERROR);
        $request = $this->request('POST', "/api/v1/{$product}")
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Idempotency-Key', $idempotencyKey ?? self::uuid())
            ->withBody($this->streamFactory->createStream($body));
        $result = $this->send($request, $envelope);
        $this->cache->set($key, $result);

        return $result;
    }

    private function request(string $method, string $path): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $this->baseUrl . $path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'ipmax-php/' . self::VERSION);

        return $this->apiKey === null ? $request : $request->withHeader('Authorization', "Bearer {$this->apiKey}");
    }

    /**
     * @template T of object
     * @param class-string<T> $envelope
     * @return T
     */
    private function send(RequestInterface $request, string $envelope): object
    {
        for ($attempt = 0; ; ++$attempt) {
            $started = hrtime(true);
            try {
                $response = $this->http->sendRequest($request);
            } catch (ClientExceptionInterface $error) {
                $failure = $this->connectionFailure($error, $started);
                if (!$error instanceof NetworkExceptionInterface || $attempt >= $this->maxRetries) {
                    throw $failure;
                }
                $this->pause($attempt, null);

                continue;
            }
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return Hydrator::hydrate($envelope, self::decode((string) $response->getBody()));
            }
            $error = self::apiException($response);
            if ($attempt >= $this->maxRetries || !self::isRetryableStatus($status)) {
                throw $error;
            }
            $this->pause($attempt, $error->getRetryAfter());
        }
    }

    private function connectionFailure(ClientExceptionInterface $error, int $started): ConnectionException
    {
        if ((hrtime(true) - $started) / 1e9 >= $this->timeout) {
            return new TimeoutException(sprintf('Request timed out after %s seconds', $this->timeout), previous: $error);
        }

        return new ConnectionException("Could not reach the IP-Max API: {$error->getMessage()}", previous: $error);
    }

    private function pause(int $attempt, ?float $retryAfter): void
    {
        $seconds = $retryAfter ?? min(self::BACKOFF_CAP, self::BACKOFF_BASE * 2 ** $attempt) * random_int(0, PHP_INT_MAX) / PHP_INT_MAX;
        usleep((int) ($seconds * 1_000_000));
    }

    private static function apiException(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $retryAfter = self::retryAfter($response->getHeaderLine('Retry-After'));
        try {
            $error = Hydrator::hydrate(ErrorResponse::class, self::decode((string) $response->getBody()))->error;
        } catch (DecodeException) {
            return ApiException::create(
                sprintf('Unexpected HTTP %d response', $status),
                $status,
                null,
                $response->getHeaderLine('X-Request-ID') ?: null,
                self::isRetryableStatus($status),
                $retryAfter,
            );
        }

        return ApiException::create($error->message, $status, $error->code, $error->requestId, $error->retryable, $retryAfter);
    }

    private static function decode(string $body): mixed
    {
        try {
            return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new DecodeException('Response body is not valid JSON', previous: $error);
        }
    }

    private static function retryAfter(string $header): ?float
    {
        return is_numeric($header) && (float) $header >= 0 ? (float) $header : null;
    }

    private static function isRetryableStatus(int $status): bool
    {
        return $status === 408 || $status === 429 || $status >= 500;
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
