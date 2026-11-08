<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

/**
 * Lưu thứ tự mới sau khi người dùng kéo thả hoặc bấm ▲/▼.
 *
 * Thứ tự ghi theo bước 10 (10, 20, 30...) để sau này chèn thêm dễ dàng.
 */
final class SortOrderService
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Tính sort_order mới cho danh sách id theo thứ tự người dùng sắp.
     *
     * @param list<int|string> $orderedIds  id theo thứ tự mới (từ biểu mẫu)
     * @param list<int>        $allowedIds  id thuộc đúng nhóm được phép sắp xếp
     * @return array<int, int> id => sort_order
     */
    public static function plan(array $orderedIds, array $allowedIds): array
    {
        $plan = [];
        $position = 0;
        foreach ($orderedIds as $rawId) {
            if (filter_var($rawId, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('Id không hợp lệ.');
            }
            $id = (int) $rawId;
            if (!in_array($id, $allowedIds, true) || isset($plan[$id])) {
                throw new InvalidArgumentException('Danh sách sắp xếp không hợp lệ.');
            }
            $plan[$id] = ++$position * 10;
        }

        return $plan;
    }

    /**
     * Ghi thứ tự mới vào bảng.
     *
     * @param string $table      tên bảng (do code truyền, không lấy từ người dùng)
     * @param string $groupWhere điều kiện SQL giới hạn nhóm, ví dụ "conference_id = ?"
     * @param list<mixed> $groupParams
     */
    public function apply(string $table, string $groupWhere, array $groupParams, array $orderedIds): void
    {
        $allowed = array_map('intval', array_column(
            $this->db->query("SELECT id FROM $table WHERE $groupWhere AND deleted_at IS NULL", $groupParams),
            'id',
        ));
        $plan = self::plan($orderedIds, $allowed);

        $this->db->transaction(function () use ($table, $plan): void {
            foreach ($plan as $id => $sortOrder) {
                $this->db->execute("UPDATE $table SET sort_order = ?, updated_at = SYSUTCDATETIME() WHERE id = ?", [$sortOrder, $id]);
            }
        });
    }
}
