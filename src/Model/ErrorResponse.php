<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class ErrorResponse
{
    public function __construct(
        public bool $status,
        public null $data,
        public ApiError $error,
    ) {}
}
