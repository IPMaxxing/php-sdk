<?php

declare(strict_types=1);

namespace IPMax\Model;

final readonly class CatalogResponse
{
    public function __construct(
        public bool $status,
        public Catalog $data,
    ) {}
}
