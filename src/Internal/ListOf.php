<?php

declare(strict_types=1);

namespace IPMax\Internal;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class ListOf
{
    public function __construct(public string $type) {}
}
