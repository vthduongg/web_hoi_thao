<?php

declare(strict_types=1);

namespace App\Models;

/** Một ảnh trên slider trang chủ (bảng slides), kèm đường dẫn ảnh. */
final class Slide
{
    public function __construct(
        public readonly int $id,
        public readonly string $imagePath,
        public readonly ?string $largeImagePath,
        public readonly ?string $title,
        public readonly string $altText,
        public readonly ?string $linkUrl,
        public readonly int $sortOrder,
        public readonly bool $isVisible,
    ) {
    }

    /** @param array<string, mixed> $row dòng slides JOIN media_files */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            imagePath: $row['stored_path'],
            largeImagePath: $row['thumb_large_path'] ?? null,
            title: $row['title'],
            altText: $row['alt_text'],
            linkUrl: $row['link_url'],
            sortOrder: (int) $row['sort_order'],
            isVisible: (bool) $row['is_visible'],
        );
    }
}
