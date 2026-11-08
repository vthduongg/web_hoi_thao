<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use InvalidArgumentException;

/**
 * Thùng rác (FR-039): liệt kê, khôi phục, xoá vĩnh viễn các mục đã xoá mềm; tự xoá vĩnh viễn sau 30 ngày.
 *
 * Mọi bảng có xoá mềm được khai báo MỘT CHỖ trong TYPES. Thứ tự khai báo cũng là thứ tự dọn dẹp
 * (bảng con trước bảng cha).
 */
final class TrashService
{
    public const KEEP_DAYS = 30;

    /**
     * bảng => [cột/biểu thức tên hiển thị, biểu thức lấy conference_id của dòng (null = dùng chung mọi hội thảo)]
     * Bí danh của bảng trong các biểu thức là "t".
     */
    private const TYPES = [
        'program_items' => ['t.content', '(SELECT d.conference_id FROM program_sessions s JOIN program_days d ON d.id = s.program_day_id WHERE s.id = t.program_session_id)'],
        'program_sessions' => ['t.title', '(SELECT d.conference_id FROM program_days d WHERE d.id = t.program_day_id)'],
        'program_days' => ['CONVERT(VARCHAR(10), t.day_date, 103)', 't.conference_id'],
        'committee_members' => ['t.full_name', '(SELECT c.conference_id FROM committees c WHERE c.id = t.committee_id)'],
        'committees' => ['t.name', 't.conference_id'],
        'sponsors' => ['t.name', 't.conference_id'],
        'sponsor_tiers' => ['t.name', 't.conference_id'],
        'keynote_speakers' => ['t.full_name', 't.conference_id'],
        'contact_persons' => ['t.full_name', 't.conference_id'],
        'important_dates' => ['t.label', 't.conference_id'],
        'slides' => ['COALESCE(t.title, t.alt_text)', 't.conference_id'],
        'highlights' => ['t.title', 't.conference_id'],
        'menu_items' => ['t.label', 't.conference_id'],
        'pages' => ['t.title', 't.conference_id'],
        'media_files' => ['t.original_name', null],
    ];

    public function __construct(
        private readonly Database $db,
        private readonly ActivityLogger $logger,
        private readonly string $publicDir,
    ) {
    }

    /**
     * Các mục trong thùng rác của hội thảo (cộng tệp thư viện — dùng chung), mới xoá trước.
     *
     * @return list<array{key: string, type: string, id: int, label: string, type_label: string, deleted_at: string, purge_at: string, deleted_by: ?string}>
     */
    public function listForConference(int $conferenceId): array
    {
        $items = [];
        foreach (self::TYPES as $table => [$labelSql, $conferenceSql]) {
            $where = $conferenceSql === null ? '' : " AND $conferenceSql = ?";
            $rows = $this->db->query(
                "SELECT t.id, $labelSql AS label, t.deleted_at, u.full_name AS deleted_by
                 FROM $table t LEFT JOIN users u ON u.id = t.deleted_by
                 WHERE t.deleted_at IS NOT NULL$where",
                $conferenceSql === null ? [] : [$conferenceId],
            );
            foreach ($rows as $row) {
                $deletedAt = substr((string) $row['deleted_at'], 0, 19);
                $items[] = [
                    'key' => "$table:{$row['id']}",
                    'type' => $table,
                    'id' => (int) $row['id'],
                    'label' => (string) $row['label'],
                    'type_label' => t('trash.type_' . $table),
                    'deleted_at' => $deletedAt,
                    'purge_at' => (new \DateTimeImmutable($deletedAt))->modify('+' . self::KEEP_DAYS . ' days')->format('Y-m-d H:i:s'),
                    'deleted_by' => $row['deleted_by'],
                ];
            }
        }
        usort($items, static fn (array $a, array $b): int => strcmp($b['deleted_at'], $a['deleted_at']));

        return $items;
    }

    /**
     * Khôi phục một mục. Trang có đường dẫn đã bị trang khác dùng thì phải truyền $newSlug.
     *
     * @throws SlugConflict
     */
    public function restore(string $type, int $id, ?int $userId, ?string $newSlug = null): void
    {
        $row = $this->deletedRow($type, $id);
        $changes = ['deleted_at' => null, 'deleted_by' => null];

        if ($type === 'pages') {
            $slug = $newSlug ?? (string) $row['slug'];
            if (SlugValidator::problem($slug) !== null || $this->slugTaken((int) $row['conference_id'], $slug, $id)) {
                throw new SlugConflict();
            }
            $changes['slug'] = $slug;
        }

        $this->db->update($type, $changes, 'id = ?', [$id]);
        $this->logger->log($userId, 'restore', $type, $id, $row['label'], $row['conference_id']);
    }

    /**
     * Xoá vĩnh viễn một mục trong thùng rác (kèm các dòng con phụ thuộc).
     *
     * @throws PurgeRefused
     */
    public function purge(string $type, int $id, ?int $userId): void
    {
        $row = $this->deletedRow($type, $id);
        $this->db->transaction(fn () => $this->deleteWithDependents($type, $id, $row));
        $this->logger->log($userId, 'purge', $type, $id, $row['label'], $row['conference_id']);
    }

    /** Xoá vĩnh viễn mọi mục đã nằm trong thùng rác quá 30 ngày. Trả về số mục đã xoá. */
    public function purgeExpired(): int
    {
        $cutoff = Clock::now()->modify('-' . self::KEEP_DAYS . ' days')->format('Y-m-d H:i:s');
        $count = 0;
        foreach (array_keys(self::TYPES) as $table) {
            foreach ($this->db->query("SELECT id FROM $table WHERE deleted_at IS NOT NULL AND deleted_at < ?", [$cutoff]) as $row) {
                try {
                    $this->purge($table, (int) $row['id'], null);
                    $count++;
                } catch (PurgeRefused) {
                    // Tệp vẫn đang được dùng: để lại, lần sau thử tiếp
                }
            }
        }

        return $count;
    }

    /** @return array<string, mixed> dòng đã xoá mềm kèm label, conference_id; hoặc lỗi nếu không có */
    private function deletedRow(string $type, int $id): array
    {
        if (!isset(self::TYPES[$type])) {
            throw new InvalidArgumentException("Loại không có trong thùng rác: $type");
        }
        [$labelSql, $conferenceSql] = self::TYPES[$type];
        $row = $this->db->first(
            "SELECT t.*, $labelSql AS label, " . ($conferenceSql ?? 'NULL') . " AS trash_conference_id
             FROM $type t WHERE t.id = ? AND t.deleted_at IS NOT NULL",
            [$id],
        ) ?? throw new InvalidArgumentException('Không tìm thấy mục trong thùng rác.');
        $row['conference_id'] = $row['trash_conference_id'] === null ? null : (int) $row['trash_conference_id'];

        return $row;
    }

    /** Xoá dòng và các dòng phụ thuộc (bảng con không tự xoá theo khoá ngoại). */
    private function deleteWithDependents(string $type, int $id, array $row): void
    {
        match ($type) {
            'pages' => $this->db->execute('UPDATE menu_items SET page_id = NULL WHERE page_id = ?', [$id]),
            'menu_items' => $this->db->execute('DELETE FROM menu_items WHERE parent_id = ?', [$id]),
            'committees' => $this->db->execute('DELETE FROM committee_members WHERE committee_id = ?', [$id]),
            'sponsor_tiers' => $this->db->execute('DELETE FROM sponsors WHERE sponsor_tier_id = ?', [$id]),
            'program_sessions' => $this->db->execute('DELETE FROM program_items WHERE program_session_id = ?', [$id]),
            'program_days' => $this->deleteProgramDayChildren($id),
            'media_files' => $this->releaseMediaReferences($id),
            default => null,
        };
        $this->db->execute("DELETE FROM $type WHERE id = ?", [$id]);
        if ($type === 'media_files') {
            $this->deleteMediaFiles($row);
        }
    }

    private function deleteProgramDayChildren(int $dayId): void
    {
        $this->db->execute('DELETE FROM program_items WHERE program_session_id IN (SELECT id FROM program_sessions WHERE program_day_id = ?)', [$dayId]);
        $this->db->execute('DELETE FROM program_sessions WHERE program_day_id = ?', [$dayId]);
    }

    /** Bỏ tham chiếu tới tệp ở các cột cho phép rỗng; từ chối nếu slider/nhà tài trợ (cột bắt buộc) vẫn dùng tệp. */
    private function releaseMediaReferences(int $mediaId): void
    {
        $required = (int) $this->db->value('SELECT (SELECT COUNT(*) FROM slides WHERE media_id = ?) + (SELECT COUNT(*) FROM sponsors WHERE logo_media_id = ?)', [$mediaId, $mediaId]);
        if ($required > 0) {
            throw new PurgeRefused(t('trash.media_in_use'));
        }
        foreach (['conferences' => ['logo_media_id', 'favicon_media_id', 'program_file_media_id'], 'pages' => ['featured_media_id'],
            'committee_members' => ['photo_media_id'], 'keynote_speakers' => ['photo_media_id']] as $table => $columns) {
            foreach ($columns as $column) {
                $this->db->execute("UPDATE $table SET $column = NULL WHERE $column = ?", [$mediaId]);
            }
        }
    }

    /** Xoá tệp gốc và ảnh thu nhỏ trên đĩa (ảnh thu nhỏ có thể trùng tệp gốc khi ảnh nhỏ). */
    private function deleteMediaFiles(array $row): void
    {
        $paths = array_unique(array_filter([$row['stored_path'], $row['thumb_small_path'], $row['thumb_large_path']]));
        foreach ($paths as $path) {
            $file = $this->publicDir . '/' . $path;
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function slugTaken(int $conferenceId, string $slug, int $exceptId): bool
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM pages WHERE conference_id = ? AND slug = ? AND id <> ? AND deleted_at IS NULL', [$conferenceId, $slug, $exceptId]) > 0;
    }
}
