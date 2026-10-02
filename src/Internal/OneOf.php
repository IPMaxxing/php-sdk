<?php

declare(strict_types=1);

namespace IPMax\Internal;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class OneOf
{
    /** @param array<string, string> $variants */
    public function __construct(
        public string $field,
        public array $variants,
        public string $fallback,
    ) {}

    public function variant(mixed $tag): string
    {
        return is_string($tag) ? $this->variants[$tag] ?? $this->fallback : $this->fallback;
    }
}
