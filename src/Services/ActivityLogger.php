<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Ghi nhật ký hoạt động (FR-037). Chỉ thêm dòng mới — không có hàm sửa hay xoá.
 *
 * Hành động hợp lệ: create, update, delete, restore, purge, publish, unpublish, login, login_failed,
 * logout, account_change, copy_conference, import, backup.
 */
final class ActivityLogger
{
    /** Địa chỉ IP của request hiện tại (App đặt giá trị này mỗi request). */
    private string $ipAddress = '';

    public function __construct(private readonly Database $db)
    {
    }

    public function setIpAddress(string $ip): void
    {
        $this->ipAddress = $ip;
    }

    public function log(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $label = null,
        ?int $conferenceId = null,
    ): void {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_label' => $label === null ? null : mb_substr($label, 0, 255),
            'conference_id' => $conferenceId,
            'ip_address' => $this->ipAddress !== '' ? $this->ipAddress : null,
        ]);
    }
}
