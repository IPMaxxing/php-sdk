<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class ApiError
{
    public function __construct(
        public int $code,
        public string $message,
        public string $requestId,
        public bool $retryable,
    ) {}
}
