<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class AbuseReports
{
    /** @param list<AbuseCategoryCount> $categories */
    public function __construct(
        public int $confidenceScore,
        public int $totalReports,
        public int $distinctReporters,
        public ?string $lastReportedAt,
        public ?bool $isWhitelisted,
        #[ListOf(AbuseCategoryCount::class)]
        public array $categories,
    ) {}
}
