<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use App\Models\ImportantDate;
use App\Repositories\PageRepository;

/** Số liệu cho bảng điều khiển (FR-021). */
final class DashboardService
{
    /** Chỉ các hành động về nội dung được hiện ở bảng điều khiển (không có đăng nhập, tài khoản, IP). */
    public const CONTENT_ACTIONS = ['create', 'update', 'delete', 'restore', 'publish', 'unpublish'];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{published: int, draft: int, hidden: int} */
    public function pageCounts(int $conferenceId): array
    {
        return (new PageRepository($this->db))->countByStatus($conferenceId);
    }

    /** @return list<ImportantDate> các mốc sắp tới (từ hôm nay, giờ Việt Nam) */
    public function upcomingDates(int $conferenceId, int $limit = 5): array
    {
        return array_map([ImportantDate::class, 'fromRow'], $this->db->query(
            "SELECT TOP ($limit) * FROM important_dates
             WHERE conference_id = ? AND is_visible = 1 AND deleted_at IS NULL AND status <> 'tba' AND date_start >= ?
             ORDER BY date_start, sort_order",
            [$conferenceId, Clock::todayInVietnam()],
        ));
    }

    /**
     * Các thay đổi nội dung gần nhất của hội thảo — chỉ lấy người thực hiện, hành động, đối tượng, thời điểm.
     *
     * @return list<array{author: ?string, action: string, entity_type: ?string, entity_label: ?string, created_at: string}>
     */
    public function recentChanges(int $conferenceId, int $limit = 10): array
    {
        $placeholders = implode(',', array_fill(0, count(self::CONTENT_ACTIONS), '?'));

        return $this->db->query(
            "SELECT TOP ($limit) u.full_name AS author, l.action, l.entity_type, l.entity_label, l.created_at
             FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
             WHERE l.conference_id = ? AND l.action IN ($placeholders)
             ORDER BY l.created_at DESC, l.id DESC",
            [$conferenceId, ...self::CONTENT_ACTIONS],
        );
    }
}
