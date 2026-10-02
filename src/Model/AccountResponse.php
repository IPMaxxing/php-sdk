<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class AccountResponse
{
    public function __construct(
        public bool $status,
        public Account $data,
    ) {}
}
