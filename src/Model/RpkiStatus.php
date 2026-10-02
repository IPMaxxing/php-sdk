<?php

declare(strict_types=1);

namespace IPMax\Model;

final class RpkiStatus
{
    public const VALID = 'valid';
    public const INVALID_ASN = 'invalid_asn';
    public const INVALID_LENGTH = 'invalid_length';
    public const UNKNOWN = 'unknown';
    public const NOT_ROUTED = 'not_routed';
}
