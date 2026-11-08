<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Truy vấn bảng media_files (thư viện tệp). */
final class MediaRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** Đường dẫn lưu của tệp (chưa bị xoá), hoặc null. */
    public function pathOf(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        $path = $this->db->value('SELECT stored_path FROM media_files WHERE id = ? AND deleted_at IS NULL', [$id]);

        return $path === null ? null : (string) $path;
    }
}
