<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\MediaFile;

/** Truy vấn bảng media_files (thư viện tệp, dùng chung mọi hội thảo). */
final class MediaRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** Đường dẫn lưu của tệp (chưa bị xoá), hoặc null. */
    public function pathOf(?int $id): ?string
    {
        return $this->find($id)?->storedPath;
    }

    public function find(?int $id): ?MediaFile
    {
        if ($id === null) {
            return null;
        }
        $row = $this->db->first('SELECT * FROM media_files WHERE id = ? AND deleted_at IS NULL', [$id]);

        return $row === null ? null : MediaFile::fromRow($row);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->db->insert('media_files', $data);
    }

    /**
     * Tìm tệp chưa xoá theo tên gốc, mới nhất trước.
     *
     * @param ?string $kind 'image' | 'document' | null (tất cả)
     * @return list<MediaFile>
     */
    public function search(string $query = '', ?string $kind = null, int $limit = 200): array
    {
        $sql = 'SELECT TOP (' . max(1, $limit) . ') * FROM media_files WHERE deleted_at IS NULL';
        $params = [];
        if ($query !== '') {
            $sql .= ' AND original_name LIKE ?';
            $params[] = '%' . addcslashes($query, '%_[') . '%';
        }
        if ($kind === 'image' || $kind === 'document') {
            $sql .= ' AND kind = ?';
            $params[] = $kind;
        }

        return array_map([MediaFile::class, 'fromRow'], $this->db->query($sql . ' ORDER BY created_at DESC, id DESC', $params));
    }
}
