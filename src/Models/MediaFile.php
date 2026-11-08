<?php

declare(strict_types=1);

namespace App\Models;

/** Một tệp trong thư viện (bảng media_files). Đường dẫn tính từ thư mục public/. */
final class MediaFile
{
    public function __construct(
        public readonly int $id,
        public readonly string $originalName,
        public readonly string $storedPath,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $kind,
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly ?string $thumbSmallPath,
        public readonly ?string $thumbLargePath,
        public readonly ?string $altText,
        public readonly ?string $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            originalName: $row['original_name'],
            storedPath: $row['stored_path'],
            mimeType: $row['mime_type'],
            sizeBytes: (int) $row['size_bytes'],
            kind: $row['kind'],
            width: $row['width'],
            height: $row['height'],
            thumbSmallPath: $row['thumb_small_path'],
            thumbLargePath: $row['thumb_large_path'],
            altText: $row['alt_text'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    /** Đường dẫn ảnh nhỏ để xem trước (ảnh thu nhỏ nếu có). */
    public function previewPath(): string
    {
        return $this->thumbSmallPath ?? $this->storedPath;
    }
}
