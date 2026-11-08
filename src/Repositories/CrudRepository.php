<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Clock;
use App\Core\Database;

/**
 * Thao tác chung cho các bảng dữ liệu có cấu trúc (mốc thời gian, thành viên ban, diễn giả...).
 *
 * Mọi truy vấn kèm điều kiện "phạm vi" (ví dụ "conference_id = ?") để không đọc/sửa nhầm dữ liệu
 * của hội thảo khác. Tên bảng và điều kiện do code truyền vào, không bao giờ lấy từ người dùng.
 */
final class CrudRepository
{
    /**
     * @param string $table       tên bảng
     * @param string $scopeWhere  điều kiện phạm vi, ví dụ "conference_id = ?"
     * @param list<mixed> $scopeParams
     */
    public function __construct(
        private readonly Database $db,
        private readonly string $table,
        private readonly string $scopeWhere,
        private readonly array $scopeParams,
    ) {
    }

    /** @return list<array<string, mixed>> các dòng chưa xoá, theo thứ tự sắp xếp */
    public function all(): array
    {
        return $this->db->query(
            "SELECT * FROM {$this->table} WHERE {$this->scopeWhere} AND deleted_at IS NULL ORDER BY sort_order, id",
            $this->scopeParams,
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->first(
            "SELECT * FROM {$this->table} WHERE id = ? AND {$this->scopeWhere} AND deleted_at IS NULL",
            [$id, ...$this->scopeParams],
        );
    }

    /** Thêm vào cuối danh sách (sort_order lớn nhất + 10). */
    public function insert(array $data): int
    {
        $next = (int) $this->db->value(
            "SELECT ISNULL(MAX(sort_order), 0) + 10 FROM {$this->table} WHERE {$this->scopeWhere} AND deleted_at IS NULL",
            $this->scopeParams,
        );

        return $this->db->insert($this->table, $data + ['sort_order' => $next]);
    }

    public function update(int $id, array $data): void
    {
        $this->db->update($this->table, $data, 'id = ?', [$id]);
    }

    public function toggleVisibility(int $id): void
    {
        $this->db->execute("UPDATE {$this->table} SET is_visible = 1 - is_visible, updated_at = SYSUTCDATETIME() WHERE id = ?", [$id]);
    }

    /** Chuyển vào thùng rác. */
    public function softDelete(int $id, ?int $userId): void
    {
        $this->db->update($this->table, ['deleted_at' => Clock::nowForDatabase(), 'deleted_by' => $userId], 'id = ?', [$id]);
    }
}
