<?php

declare(strict_types=1);

namespace App\Models;

/** Một ban / hội đồng (bảng committees) kèm danh sách thành viên đang hiện. */
final class Committee
{
    /** @param list<CommitteeMember> $members */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $members = [],
    ) {
    }
}
