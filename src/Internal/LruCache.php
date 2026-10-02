<?php

declare(strict_types=1);

namespace IPMax\Internal;

final class LruCache
{
    /** @var array<string, array{float, object}> */
    private array $entries = [];

    public function __construct(
        private readonly int $capacity,
        private readonly float $ttl,
    ) {}

    public function get(string $key): ?object
    {
        $entry = $this->entries[$key] ?? null;
        if ($entry === null) {
            return null;
        }
        unset($this->entries[$key]);
        if ($entry[0] <= self::now()) {
            return null;
        }
        $this->entries[$key] = $entry;

        return $entry[1];
    }

    public function set(string $key, object $value): void
    {
        if ($this->capacity <= 0) {
            return;
        }
        unset($this->entries[$key]);
        $this->entries[$key] = [self::now() + $this->ttl, $value];
        if (count($this->entries) > $this->capacity) {
            unset($this->entries[array_key_first($this->entries)]);
        }
    }

    private static function now(): float
    {
        return hrtime(true) / 1e9;
    }
}
