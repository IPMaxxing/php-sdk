<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class AbuseCategoryCount
{
    public function __construct(
        public int $id,
        public int $count,
    ) {}
}
