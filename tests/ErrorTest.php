<?php

declare(strict_types=1);

namespace IPMax\Tests;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use Http\Message\RequestMatcher\RequestMatcher;
use IPMax\Exception\ApiException;
use IPMax\Exception\AuthenticationException;
use IPMax\Exception\ConflictException;
use IPMax\Exception\ConnectionException;
use IPMax\Exception\InsufficientBalanceException;
use IPMax\Exception\InvalidRequestException;
use IPMax\Exception\IPMaxException;
use IPMax\Exception\NotFoundException;
use IPMax\Exception\RateLimitException;
use IPMax\Exception\ServerException;
use IPMax\Exception\TimeoutException;
use IPMax\Model\ErrorCode;
use Psr\Http\Message\RequestInterface;
use Throwable;

final class ErrorTest extends TestCase
{
    public function testInvalidRequestFixture(): void
    {
        $this->queue(self::fixture('error-invalid-request'));

        $error = $this->catch(fn() => $this->client()->geoip('not-an-ip'));

        self::assertInstanceOf(InvalidRequestException::class, $error);
        self::assertSame(400, $error->getStatus());
        self::assertSame(ErrorCode::INVALID_REQUEST, $error->getErrorCode());
        self::assertSame(ErrorCode::INVALID_REQUEST, $error->getCode());
        self::assertSame('A valid IP and 16-120 character Idempotency-Key are required', $error->getMessage());
        self::assertSame('ENT-9b178079-212d-4052-ae72-8edf564be6e1', $error->getRequestId());
        self::assertFalse($error->isRetryable());
        self::assertNull($error->getRetryAfter());
    }

    public function testNotFoundFixture(): void
    {
        $this->queue(self::fixture('error-not-found'));

        $error = $this->catch(fn() => $this->client()->intelligence('192.0.2.1'));

        self::assertInstanceOf(NotFoundException::class, $error);
        self::assertSame(404, $error->getStatus());
        self::assertSame(ErrorCode::IP_NOT_FOUND, $error->getErrorCode());
        self::assertSame('ENT-f1b619f1-4da9-48c6-bb41-8ac819ea863a', $error->getRequestId());
    }

    public function testUnauthorizedFixture(): void
    {
        $this->queue(self::fixture('error-unauthorized'));

        $error = $this->catch(fn() => $this->client()->account());

        self::assertInstanceOf(AuthenticationException::class, $error);
        self::assertInstanceOf(IPMaxException::class, $error);
        self::assertSame(401, $error->getStatus());
        self::assertSame(ErrorCode::INVALID_API_KEY, $error->getErrorCode());
        self::assertSame('Invalid API key', $error->getMessage());
    }

    public function testStatusesMapToExceptionTypes(): void
    {
        $expected = [
            402 => InsufficientBalanceException::class,
            409 => ConflictException::class,
            413 => InvalidRequestException::class,
            415 => InvalidRequestException::class,
            429 => RateLimitException::class,
            500 => ServerException::class,
            503 => ServerException::class,
            418 => ApiException::class,
        ];
        $client = $this->client(maxRetries: 0);

        foreach ($expected as $status => $type) {
            $this->queue(self::error($status, 1501));

            $error = $this->catch(fn() => $client->geoip('8.8.8.8'));

            self::assertSame($type, $error::class);
            self::assertSame($status, $error->getStatus());
            self::assertSame(1501, $error->getErrorCode());
            self::assertSame("req-{$status}", $error->getRequestId());
        }
    }

    public function testRetryAfterIsExposed(): void
    {
        $this->queue(self::error(429, ErrorCode::RATE_LIMIT_EXCEEDED, '1.5'));

        $error = $this->catch(fn() => $this->client(maxRetries: 0)->catalog());

        self::assertInstanceOf(RateLimitException::class, $error);
        self::assertSame(1.5, $error->getRetryAfter());
    }

    public function testNonJsonErrorBodyStillCarriesStatus(): void
    {
        $this->queue(new Response(502, ['X-Request-ID' => 'edge-42', 'Content-Type' => 'text/html'], '<html>Bad gateway</html>'));

        $error = $this->catch(fn() => $this->client(maxRetries: 0)->geoip('8.8.8.8'));

        self::assertInstanceOf(ServerException::class, $error);
        self::assertSame(502, $error->getStatus());
        self::assertNull($error->getErrorCode());
        self::assertSame('Unexpected HTTP 502 response', $error->getMessage());
        self::assertSame('edge-42', $error->getRequestId());
        self::assertTrue($error->isRetryable());
    }

    public function testUnexpectedJsonErrorBodyStillCarriesStatus(): void
    {
        $this->queue(new Response(404, body: '{"message": "no route"}'));

        $error = $this->catch(fn() => $this->client()->account());

        self::assertInstanceOf(NotFoundException::class, $error);
        self::assertSame(404, $error->getStatus());
        self::assertNull($error->getRequestId());
        self::assertFalse($error->isRetryable());
    }

    public function testUnknownErrorCodeIsKept(): void
    {
        $this->queue(self::error(400, 4242));

        $error = $this->catch(fn() => $this->client()->geoip('8.8.8.8'));

        self::assertSame(4242, $error->getErrorCode());
    }

    public function testNetworkFailureBecomesConnectionException(): void
    {
        $cause = new NetworkException('Connection refused', new Request('GET', '/'));
        $this->http->addException($cause);

        try {
            $this->client(maxRetries: 0)->catalog();
            self::fail('Expected a connection failure');
        } catch (ConnectionException $error) {
            self::assertNotInstanceOf(TimeoutException::class, $error);
            self::assertInstanceOf(IPMaxException::class, $error);
            self::assertSame($cause, $error->getPrevious());
            self::assertStringContainsString('Connection refused', $error->getMessage());
        }
    }

    public function testSlowFailureBecomesTimeoutException(): void
    {
        $this->http->on(new RequestMatcher(), static function (RequestInterface $request): never {
            usleep(30_000);

            throw new NetworkException('Operation timed out', $request);
        });

        $this->expectException(TimeoutException::class);

        $this->client(timeout: 0.02, maxRetries: 0)->catalog();
    }

    private function catch(callable $call): ApiException
    {
        try {
            $call();
        } catch (Throwable $error) {
            self::assertInstanceOf(ApiException::class, $error);

            return $error;
        }
        self::fail('Expected an API exception');
    }
}
