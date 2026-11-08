<?php

declare(strict_types=1);

namespace App\Models;

/** Một hạng nhà tài trợ (bảng sponsor_tiers) kèm các nhà tài trợ đang hiện. */
final class SponsorTier
{
    /** @param list<Sponsor> $sponsors */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $sponsors = [],
    ) {
    }
}
