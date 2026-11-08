<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Committee;
use App\Models\CommitteeMember;

/** Truy vấn ban, thành viên ban và việc gắn ban vào trang. */
final class CommitteeRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<Committee> các ban đang hiện được gắn vào trang, kèm thành viên đang hiện */
    public function visibleForPage(int $pageId): array
    {
        $rows = $this->db->query(
            'SELECT c.id, c.name FROM committees c JOIN page_committees pc ON pc.committee_id = c.id
             WHERE pc.page_id = ? AND c.is_visible = 1 AND c.deleted_at IS NULL
             ORDER BY c.sort_order, c.id',
            [$pageId],
        );

        return array_map(fn (array $row): Committee => new Committee((int) $row['id'], $row['name'], $this->visibleMembers((int) $row['id'])), $rows);
    }

    /** @return list<CommitteeMember> */
    public function visibleMembers(int $committeeId): array
    {
        return array_map([CommitteeMember::class, 'fromRow'], $this->db->query(
            'SELECT m.*, f.stored_path AS photo_path
             FROM committee_members m LEFT JOIN media_files f ON f.id = m.photo_media_id AND f.deleted_at IS NULL
             WHERE m.committee_id = ? AND m.is_visible = 1 AND m.deleted_at IS NULL
             ORDER BY m.sort_order, m.id',
            [$committeeId],
        ));
    }

    /** @return list<int> */
    public function pageIdsFor(int $committeeId): array
    {
        return array_map('intval', array_column($this->db->query('SELECT page_id FROM page_committees WHERE committee_id = ?', [$committeeId]), 'page_id'));
    }

    /** @param list<int> $pageIds */
    public function setPages(int $committeeId, array $pageIds): void
    {
        $this->db->execute('DELETE FROM page_committees WHERE committee_id = ?', [$committeeId]);
        foreach (array_unique($pageIds) as $pageId) {
            $this->db->insertRow('page_committees', ['page_id' => $pageId, 'committee_id' => $committeeId]);
        }
    }

    /** Thêm nhiều thành viên vào cuối danh sách (dùng khi nhập tệp). */
    public function appendMembers(int $committeeId, array $members): void
    {
        $next = (int) $this->db->value('SELECT ISNULL(MAX(sort_order), 0) FROM committee_members WHERE committee_id = ? AND deleted_at IS NULL', [$committeeId]);
        foreach ($members as $member) {
            $next += 10;
            $this->db->insert('committee_members', $member + ['committee_id' => $committeeId, 'sort_order' => $next]);
        }
    }
}
