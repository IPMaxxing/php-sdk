# ipmax/ipmax

PHP client for the [IP-Max](https://ipm.ax) GeoIP and IP intelligence API, with fully typed readonly responses.

## Install

```sh
composer require ipmax/ipmax guzzlehttp/guzzle
```

Requires PHP 8.2 or newer. Any PSR-18 client works; Guzzle is the default when it is installed, otherwise one is found through `php-http/discovery`.

## Quickstart

```php
use IPMax\Client;
use IPMax\Model\NetworkClass;

$client = new Client('sg_live_...');

$geo = $client->geoip('8.8.8.8');
echo $geo->geo->city, ' ', $geo->geo->countryCode, ' ', $geo->as->name, PHP_EOL;

$intel = $client->intelligence('8.8.8.8');
if ($intel->networkClass->primary === NetworkClass::HOSTING) {
    echo 'hosting network', PHP_EOL;
}

$catalog = $client->catalog();
$account = $client->account();
```

When no key is passed, the client reads `IPMAX_API_KEY` from the environment. `catalog()` works without a key.

Every lookup is billed and sends a fresh `Idempotency-Key`, which is reused when the request is retried. Pass your own with `$client->geoip($ip, idempotencyKey: '...')` to make a lookup safe to repeat across processes.

String enums such as `NetworkClass`, `RpkiStatus` or `LiveIntelligenceTag` are plain strings with constants for the known values, so new values from the API never break decoding. PTR results are `PtrLocatedIntelligence`, `PtrAsnMismatchIntelligence`, `PtrUnmatchedIntelligence`, or `PtrOtherIntelligence` for a status this version does not know yet.

## Configuration

All options are named constructor arguments.

| Option | Default | Description |
| --- | --- | --- |
| `apiKey` | `IPMAX_API_KEY` | API key sent as a bearer token |
| `http` | Guzzle or discovered | Your own PSR-18 client, for proxies or custom transports |
| `requestFactory`, `streamFactory` | discovered | PSR-17 factories |
| `baseUrl` | `https://api.ipm.ax` | API origin |
| `timeout` | `10.0` | Seconds per attempt; applied to the default Guzzle client, configure your own client's timeout when you inject one |
| `maxRetries` | `2` | Retries on network errors, 408, 429 and 5xx, with exponential backoff and `Retry-After` support |
| `cacheSize` | `1024` | Successful lookups kept in memory; `0` disables the cache |
| `cacheTtl` | `300` | Seconds a cached lookup stays fresh |

```php
$client = new Client(timeout: 5.0, maxRetries: 4, cacheSize: 0);
```

Cached lookups make no request and cost nothing. `catalog()` and `account()` are never cached. A lookup with an explicit idempotency key always goes to the API.

## Error handling

```php
use IPMax\Exception\ApiException;
use IPMax\Exception\ConnectionException;
use IPMax\Exception\InsufficientBalanceException;
use IPMax\Exception\RateLimitException;

try {
    $client->geoip('8.8.8.8');
} catch (InsufficientBalanceException $error) {
    notifyBilling($error->getRequestId());
} catch (RateLimitException $error) {
    sleep((int) ceil($error->getRetryAfter() ?? 1));
} catch (ApiException $error) {
    echo $error->getStatus(), ' ', $error->getErrorCode(), ' ', $error->getMessage(), ' ', $error->getRequestId();
} catch (ConnectionException $error) {
    echo 'IP-Max is unreachable: ', $error->getMessage();
}
```

| Exception | When |
| --- | --- |
| `IPMaxException` | Interface implemented by everything below |
| `ApiException` | Any non-2xx response; `getStatus()`, `getErrorCode()`, `getMessage()`, `getRequestId()`, `isRetryable()`, `getRetryAfter()` |
| `InvalidRequestException` | 400, 413, 415 |
| `AuthenticationException` | 401 |
| `InsufficientBalanceException` | 402 |
| `NotFoundException` | 404 |
| `ConflictException` | 409 |
| `RateLimitException` | 429 |
| `ServerException` | 5xx |
| `ConnectionException` | The request could not be completed |
| `TimeoutException` | The request timed out; a subclass of `ConnectionException` |
| `DecodeException` | A successful response did not match the API contract |

`IPMax\Model\ErrorCode` holds the known values of `getErrorCode()`, such as `ErrorCode::INSUFFICIENT_BALANCE`. New codes may appear and are passed through as plain integers.

## License

[Apache-2.0](LICENSE)
