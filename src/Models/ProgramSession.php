<?php

declare(strict_types=1);

namespace App\Models;

/** Một phiên trong chương trình (bảng program_sessions) kèm các mục. */
final class ProgramSession
{
    /** @param list<ProgramItem> $items */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $room,
        public readonly array $items = [],
    ) {
    }
}
