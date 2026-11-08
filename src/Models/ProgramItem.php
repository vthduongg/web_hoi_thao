<?php

declare(strict_types=1);

namespace App\Models;

/** Một mục trong phiên (bảng program_items). Giờ dạng HH:MM. */
final class ProgramItem
{
    public function __construct(
        public readonly int $id,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $content,
        public readonly ?string $presenter,
        public readonly ?string $room,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            startTime: substr((string) $row['start_time'], 0, 5),
            endTime: substr((string) $row['end_time'], 0, 5),
            content: $row['content'],
            presenter: $row['presenter'],
            room: $row['room'],
        );
    }
}
