<?php

declare(strict_types=1);

namespace IPMax\Model;

use IPMax\Internal\OneOf;

#[OneOf(
    field: 'status',
    variants: [
        'located' => PtrLocatedIntelligence::class,
        'asn_mismatch' => PtrAsnMismatchIntelligence::class,
        'unmatched' => PtrUnmatchedIntelligence::class,
    ],
    fallback: PtrOtherIntelligence::class,
)]
interface PtrIntelligence {}
