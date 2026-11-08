<?php

declare(strict_types=1);

namespace App\Models;

/** Một diễn giả chính (bảng keynote_speakers). */
final class KeynoteSpeaker
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $photoPath,
        public readonly ?string $academicTitle,
        public readonly string $fullName,
        public readonly ?string $affiliation,
        public readonly ?string $country,
        public readonly ?string $talkTitle,
        public readonly string $bioHtml,
    ) {
    }

    /** @param array<string, mixed> $row dòng keynote_speakers kèm cột photo_path */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            photoPath: $row['photo_path'] ?? null,
            academicTitle: $row['academic_title'],
            fullName: $row['full_name'],
            affiliation: $row['affiliation'],
            country: $row['country'],
            talkTitle: $row['talk_title'],
            bioHtml: (string) $row['bio_html'],
        );
    }

    public function displayName(): string
    {
        return trim(($this->academicTitle ?? '') . ' ' . $this->fullName);
    }
}
