<?php

declare(strict_types=1);

namespace IPMax\Exception;

use RuntimeException;

class ApiException extends RuntimeException implements IPMaxException
{
    public function __construct(
        string $message,
        private readonly int $status,
        private readonly ?int $errorCode,
        private readonly ?string $requestId,
        private readonly bool $retryable,
        private readonly ?float $retryAfter,
    ) {
        parent::__construct($message, $errorCode ?? 0);
    }

    public static function create(
        string $message,
        int $status,
        ?int $errorCode,
        ?string $requestId,
        bool $retryable,
        ?float $retryAfter,
    ): self {
        $arguments = [$message, $status, $errorCode, $requestId, $retryable, $retryAfter];

        return match ($status) {
            400, 413, 415 => new InvalidRequestException(...$arguments),
            401 => new AuthenticationException(...$arguments),
            402 => new InsufficientBalanceException(...$arguments),
            404 => new NotFoundException(...$arguments),
            409 => new ConflictException(...$arguments),
            429 => new RateLimitException(...$arguments),
            default => $status >= 500 ? new ServerException(...$arguments) : new self(...$arguments),
        };
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
