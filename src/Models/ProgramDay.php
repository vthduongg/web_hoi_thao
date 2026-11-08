<?php

declare(strict_types=1);

namespace App\Models;

/** Một ngày trong chương trình (bảng program_days) kèm các phiên. */
final class ProgramDay
{
    /** @param list<ProgramSession> $sessions */
    public function __construct(
        public readonly int $id,
        public readonly string $dayDate,
        public readonly ?string $title,
        public readonly array $sessions = [],
    ) {
    }
}
