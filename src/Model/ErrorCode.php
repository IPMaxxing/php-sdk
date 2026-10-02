<?php

declare(strict_types=1);

namespace IPMax\Model;

final class ErrorCode
{
    public const INVALID_REQUEST = 1000;
    public const INVALID_PAYLOAD = 1001;
    public const INVALID_IP = 1002;
    public const IP_NOT_FOUND = 1003;
    public const ROUTE_NOT_FOUND = 1004;
    public const INVALID_API_KEY = 1010;
    public const RATE_LIMIT_EXCEEDED = 1011;
    public const SERVICE_UNAVAILABLE = 1400;
    public const INTERNAL_ERROR = 1401;
    public const INSUFFICIENT_BALANCE = 1500;
    public const IDEMPOTENCY_KEY_CONFLICT = 1501;
}
