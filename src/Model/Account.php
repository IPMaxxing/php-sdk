<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\ListOf;

final readonly class Account
{
    /**
     * @param list<Wallet> $wallets
     * @param list<LedgerEntry> $entries
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $keyPrefix,
        #[ListOf(Wallet::class)]
        public array $wallets,
        #[ListOf(LedgerEntry::class)]
        public array $entries,
    ) {}
}
