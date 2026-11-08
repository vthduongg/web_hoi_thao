<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Clock;
use App\Core\Response;

/** Nhật ký hoạt động (FR-037): chỉ quản trị viên xem; chỉ đọc, không có chức năng sửa/xoá. */
final class ActivityController extends AdminController
{
    private const PER_PAGE = 50;

    public function index(): Response
    {
        $filters = [
            'user' => $this->request->input('user'),
            'action' => $this->request->input('action'),
            'from' => $this->request->input('from'),
            'to' => $this->request->input('to'),
        ];
        $page = max(1, (int) $this->request->input('page', '1'));
        [$where, $params] = $this->conditions($filters);
        // OFFSET/FETCH cần số nguyên thật (driver gửi tham số dạng chuỗi) — hai số này do code tính nên an toàn
        $offset = ($page - 1) * self::PER_PAGE;
        $fetch = self::PER_PAGE + 1; // lấy thêm 1 dòng để biết còn trang sau

        $rows = $this->services->db->query(
            "SELECT l.*, u.full_name AS author FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
             WHERE $where ORDER BY l.created_at DESC, l.id DESC
             OFFSET $offset ROWS FETCH NEXT $fetch ROWS ONLY",
            $params,
        );

        return $this->render('admin/activity/index', [
            'pageTitle' => t('nav.activity'),
            'rows' => array_slice($rows, 0, self::PER_PAGE),
            'hasNextPage' => count($rows) > self::PER_PAGE,
            'page' => $page,
            'filters' => $filters,
            'users' => $this->services->db->query('SELECT id, full_name FROM users ORDER BY full_name'),
        ]);
    }

    /**
     * Điều kiện lọc: người thực hiện, hành động, khoảng ngày (ngày theo giờ Việt Nam).
     *
     * @return array{0: string, 1: list<mixed>}
     */
    private function conditions(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (ctype_digit($filters['user'])) {
            $where[] = 'l.user_id = ?';
            $params[] = (int) $filters['user'];
        }
        if ($filters['action'] !== '' && preg_match('/^[a-z_]+$/', $filters['action'])) {
            $where[] = 'l.action = ?';
            $params[] = $filters['action'];
        }
        if ($this->validate(['d' => $filters['from']], ['d' => 'date']) === [] && $filters['from'] !== '') {
            $where[] = 'l.created_at >= ?';
            $params[] = Clock::vietnamLocalToUtc($filters['from'] . 'T00:00');
        }
        if ($this->validate(['d' => $filters['to']], ['d' => 'date']) === [] && $filters['to'] !== '') {
            $where[] = 'l.created_at < ?';
            $params[] = Clock::vietnamLocalToUtc((new \DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d') . 'T00:00');
        }

        return [implode(' AND ', $where), $params];
    }
}
