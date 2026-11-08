<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ProgramDay;
use App\Models\ProgramItem;
use App\Models\ProgramSession;

/** Đọc chương trình hội thảo: ngày → phiên → mục. */
final class ProgramRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Chương trình cho trang công khai: chỉ mục đang hiện; phiên/ngày không còn mục nào thì bỏ.
     *
     * @return list<ProgramDay>
     */
    public function publicProgram(int $conferenceId): array
    {
        $days = [];
        foreach ($this->tree($conferenceId, onlyVisibleItems: true) as $day) {
            $sessions = array_values(array_filter($day->sessions, static fn (ProgramSession $s): bool => $s->items !== []));
            if ($sessions !== []) {
                $days[] = new ProgramDay($day->id, $day->dayDate, $day->title, $sessions);
            }
        }

        return $days;
    }

    /** @return list<ProgramDay> toàn bộ chương trình (kể cả phiên trống) cho trang quản trị */
    public function tree(int $conferenceId, bool $onlyVisibleItems = false): array
    {
        $days = [];
        foreach ($this->db->query('SELECT * FROM program_days WHERE conference_id = ? AND deleted_at IS NULL ORDER BY day_date, sort_order, id', [$conferenceId]) as $day) {
            $days[] = new ProgramDay((int) $day['id'], substr((string) $day['day_date'], 0, 10), $day['title'], $this->sessions((int) $day['id'], $onlyVisibleItems));
        }

        return $days;
    }

    /** @return list<ProgramSession> */
    private function sessions(int $dayId, bool $onlyVisibleItems): array
    {
        $sessions = [];
        foreach ($this->db->query('SELECT * FROM program_sessions WHERE program_day_id = ? AND deleted_at IS NULL ORDER BY sort_order, id', [$dayId]) as $row) {
            $items = $this->db->query(
                'SELECT * FROM program_items WHERE program_session_id = ? AND deleted_at IS NULL' . ($onlyVisibleItems ? ' AND is_visible = 1' : '') . ' ORDER BY start_time, sort_order, id',
                [(int) $row['id']],
            );
            $sessions[] = new ProgramSession((int) $row['id'], $row['title'], $row['room'], array_map([ProgramItem::class, 'fromRow'], $items));
        }

        return $sessions;
    }

    /** Ngày thuộc hội thảo (chưa xoá)? */
    public function dayBelongsTo(int $dayId, int $conferenceId): bool
    {
        return $this->db->value('SELECT id FROM program_days WHERE id = ? AND conference_id = ? AND deleted_at IS NULL', [$dayId, $conferenceId]) !== null;
    }

    /** Phiên thuộc hội thảo (chưa xoá)? */
    public function sessionBelongsTo(int $sessionId, int $conferenceId): bool
    {
        return $this->db->value(
            'SELECT s.id FROM program_sessions s JOIN program_days d ON d.id = s.program_day_id
             WHERE s.id = ? AND d.conference_id = ? AND s.deleted_at IS NULL AND d.deleted_at IS NULL',
            [$sessionId, $conferenceId],
        ) !== null;
    }
}
