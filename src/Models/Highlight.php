<?php

declare(strict_types=1);

namespace App\Models;

/** Một khối thông tin nổi bật trên trang chủ (bảng highlights). */
final class Highlight
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $bodyText,
        public readonly ?string $linkUrl,
        public readonly int $sortOrder,
        public readonly bool $isVisible,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            title: $row['title'],
            bodyText: $row['body_text'],
            linkUrl: $row['link_url'],
            sortOrder: (int) $row['sort_order'],
            isVisible: (bool) $row['is_visible'],
        );
    }
}
