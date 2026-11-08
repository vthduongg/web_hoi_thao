<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ImportantDate;

/** Truy vấn bảng important_dates. */
final class ImportantDateRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<ImportantDate> mốc đang hiện của hội thảo, theo thứ tự sắp xếp */
    public function visibleForConference(int $conferenceId): array
    {
        return array_map([ImportantDate::class, 'fromRow'], $this->db->query(
            'SELECT * FROM important_dates
             WHERE conference_id = ? AND is_visible = 1 AND deleted_at IS NULL
             ORDER BY sort_order, id',
            [$conferenceId],
        ));
    }

    /** Mốc có vai trò đặc biệt (abstract_deadline, full_paper_deadline, conference_day), chỉ khi đang hiện. */
    public function findVisibleByRole(int $conferenceId, string $role): ?ImportantDate
    {
        $row = $this->db->first(
            'SELECT * FROM important_dates
             WHERE conference_id = ? AND special_role = ? AND is_visible = 1 AND deleted_at IS NULL',
            [$conferenceId, $role],
        );

        return $row === null ? null : ImportantDate::fromRow($row);
    }
}
