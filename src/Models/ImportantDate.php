<?php

declare(strict_types=1);

namespace App\Models;

/** Một mốc thời gian (bảng important_dates). Ngày dạng chuỗi Y-m-d. */
final class ImportantDate
{
    public const STATUS_NORMAL = 'normal';
    public const STATUS_EXTENDED = 'extended';
    public const STATUS_TBA = 'tba';

    public function __construct(
        public readonly int $id,
        public readonly int $conferenceId,
        public readonly string $label,
        public readonly string $status,
        public readonly ?string $dateStart,
        public readonly ?string $dateEnd,
        public readonly ?string $originalDate,
        public readonly ?string $specialRole,
        public readonly int $sortOrder,
        public readonly bool $isVisible,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            conferenceId: (int) $row['conference_id'],
            label: $row['label'],
            status: $row['status'],
            dateStart: self::dateOnly($row['date_start']),
            dateEnd: self::dateOnly($row['date_end']),
            originalDate: self::dateOnly($row['original_date']),
            specialRole: $row['special_role'],
            sortOrder: (int) $row['sort_order'],
            isVisible: (bool) $row['is_visible'],
        );
    }

    public function isTba(): bool
    {
        return $this->status === self::STATUS_TBA;
    }

    public function isExtended(): bool
    {
        return $this->status === self::STATUS_EXTENDED;
    }

    private static function dateOnly(mixed $value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 10);
    }
}
