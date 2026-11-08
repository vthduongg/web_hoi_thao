<?php

declare(strict_types=1);

namespace App\Models;

/** Một nhà tài trợ / đối tác (bảng sponsors). */
final class Sponsor
{
    public function __construct(
        public readonly int $id,
        public readonly int $tierId,
        public readonly string $name,
        public readonly string $logoPath,
        public readonly ?string $websiteUrl,
        public readonly ?string $logoSmallPath = null,
    ) {
    }

    /** @param array<string, mixed> $row dòng sponsors kèm cột logo_path */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            tierId: (int) $row['sponsor_tier_id'],
            name: $row['name'],
            logoPath: $row['logo_path'],
            websiteUrl: $row['website_url'],
            logoSmallPath: $row['logo_small_path'] ?? null,
        );
    }
}
