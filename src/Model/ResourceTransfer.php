<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class ResourceTransfer
{
    public function __construct(
        public string $transferTime,
        public string $transferType,
        public string $sourceResource,
        public string $recipientResource,
        public string $sourceRegistry,
        public string $recipientRegistry,
        public string $sourceHolder,
        public string $recipientHolder,
    ) {}
}
