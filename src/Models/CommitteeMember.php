<?php

declare(strict_types=1);

namespace App\Models;

/** Một thành viên ban (bảng committee_members). */
final class CommitteeMember
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $academicTitle,
        public readonly string $fullName,
        public readonly ?string $positionAffiliation,
        public readonly ?string $committeeRole,
        public readonly ?string $photoPath,
    ) {
    }

    /** @param array<string, mixed> $row dòng committee_members (kèm cột photo_path nếu có) */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            academicTitle: $row['academic_title'],
            fullName: $row['full_name'],
            positionAffiliation: $row['position_affiliation'],
            committeeRole: $row['committee_role'],
            photoPath: $row['photo_path'] ?? null,
        );
    }

    /** "Prof. Nguyễn Văn An" */
    public function displayName(): string
    {
        return trim(($this->academicTitle ?? '') . ' ' . $this->fullName);
    }
}
