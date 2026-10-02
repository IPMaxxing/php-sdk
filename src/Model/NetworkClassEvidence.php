<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class NetworkClassEvidence
{
    public function __construct(
        public string $class,
        public string $source,
        public string $scope,
        public string $value,
    ) {}
}
