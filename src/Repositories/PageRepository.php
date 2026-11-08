<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Clock;
use App\Core\Database;
use App\Models\Page;

/** Truy vấn bảng pages (và page_tags, slug_redirects). */
final class PageRepository
{
    /** Điều kiện SQL "trang công khai": đã xuất bản, tới giờ xuất bản, chưa bị xoá. */
    private const PUBLIC_CONDITION = "status = 'published' AND published_at <= ? AND deleted_at IS NULL";

    public function __construct(private readonly Database $db)
    {
    }

    public function findPublicBySlug(int $conferenceId, string $slug): ?Page
    {
        $row = $this->db->first(
            'SELECT * FROM pages WHERE conference_id = ? AND slug = ? AND ' . self::PUBLIC_CONDITION,
            [$conferenceId, $slug, Clock::nowForDatabase()],
        );

        return $row === null ? null : Page::fromRow($row);
    }

    /** Slug hiện tại của trang mà slug cũ đã chuyển hướng tới (chỉ khi trang đó công khai). */
    public function findRedirect(int $conferenceId, string $oldSlug): ?string
    {
        $slug = $this->db->value(
            "SELECT p.slug FROM slug_redirects r JOIN pages p ON p.id = r.page_id
             WHERE r.conference_id = ? AND r.old_slug = ?
               AND p.status = 'published' AND p.published_at <= ? AND p.deleted_at IS NULL",
            [$conferenceId, $oldSlug, Clock::nowForDatabase()],
        );

        return $slug === null ? null : (string) $slug;
    }

    /** @return list<Page> mọi trang công khai của hội thảo */
    public function publicPagesForConference(int $conferenceId): array
    {
        return array_map([Page::class, 'fromRow'], $this->db->query(
            'SELECT * FROM pages WHERE conference_id = ? AND ' . self::PUBLIC_CONDITION . ' ORDER BY title',
            [$conferenceId, Clock::nowForDatabase()],
        ));
    }

    // ===== Phần quản trị =====

    /** Trang chưa xoá của hội thảo (mọi trạng thái). */
    public function findForAdmin(int $conferenceId, int $id): ?Page
    {
        $row = $this->db->first('SELECT * FROM pages WHERE id = ? AND conference_id = ? AND deleted_at IS NULL', [$id, $conferenceId]);

        return $row === null ? null : Page::fromRow($row);
    }

    /**
     * Danh sách trang để quản lý, lọc theo trạng thái, thẻ, từ khoá trong tiêu đề.
     *
     * @return list<Page>
     */
    public function listForAdmin(int $conferenceId, string $status = '', string $tag = '', string $query = ''): array
    {
        $sql = 'SELECT p.* FROM pages p WHERE p.conference_id = ? AND p.deleted_at IS NULL';
        $params = [$conferenceId];
        if (in_array($status, ['draft', 'published', 'hidden'], true)) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        if ($tag !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM page_tags t WHERE t.page_id = p.id AND t.tag = ?)';
            $params[] = $tag;
        }
        if ($query !== '') {
            $sql .= ' AND p.title LIKE ?';
            $params[] = '%' . addcslashes($query, '%_[') . '%';
        }

        return array_map([Page::class, 'fromRow'], $this->db->query($sql . ' ORDER BY p.title', $params));
    }

    public function slugTaken(int $conferenceId, string $slug, ?int $exceptId): bool
    {
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM pages WHERE conference_id = ? AND slug = ? AND id <> ? AND deleted_at IS NULL',
            [$conferenceId, $slug, $exceptId ?? 0],
        ) > 0;
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        return $this->db->insert('pages', $data);
    }

    /**
     * Cập nhật chỉ khi số phiên bản còn khớp (khoá lạc quan); tăng phiên bản thêm 1.
     * Trả về false nếu trang đã bị người khác sửa.
     *
     * @param array<string, mixed> $data
     */
    public function updateIfVersion(int $id, array $data, int $expectedVersion): bool
    {
        return $this->db->update('pages', $data + ['version' => $expectedVersion + 1], 'id = ? AND version = ?', [$id, $expectedVersion]) === 1;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $this->db->update('pages', $data, 'id = ?', [$id]);
    }

    /** @param list<string> $tags */
    public function saveTags(int $pageId, array $tags): void
    {
        $this->db->execute('DELETE FROM page_tags WHERE page_id = ?', [$pageId]);
        foreach ($tags as $tag) {
            $this->db->insertRow('page_tags', ['page_id' => $pageId, 'tag' => $tag]);
        }
    }

    /** Lưu ảnh chụp nội dung hiện tại của trang thành phiên bản mới. */
    public function addRevision(int $pageId, ?int $userId): void
    {
        $this->db->execute(
            'INSERT INTO page_revisions (page_id, revision_no, title, body_html, seo_title, seo_description, featured_media_id, module, created_by)
             SELECT id, (SELECT ISNULL(MAX(revision_no), 0) + 1 FROM page_revisions WHERE page_id = ?),
                    title, body_html, seo_title, seo_description, featured_media_id, module, ?
             FROM pages WHERE id = ?',
            [$pageId, $userId, $pageId],
        );
    }

    /** @return list<array<string, mixed>> phiên bản mới nhất trước, kèm tên người lưu */
    public function revisions(int $pageId): array
    {
        return $this->db->query(
            'SELECT r.revision_no, r.title, r.created_at, u.full_name AS author
             FROM page_revisions r LEFT JOIN users u ON u.id = r.created_by
             WHERE r.page_id = ? ORDER BY r.revision_no DESC',
            [$pageId],
        );
    }

    /** @return array<string, mixed>|null */
    public function findRevision(int $pageId, int $revisionNo): ?array
    {
        return $this->db->first('SELECT * FROM page_revisions WHERE page_id = ? AND revision_no = ?', [$pageId, $revisionNo]);
    }

    public function addRedirect(int $conferenceId, string $oldSlug, int $pageId): void
    {
        // Slug cũ có thể từng chuyển hướng tới trang khác — thay bằng chuyển hướng mới
        $this->db->execute('DELETE FROM slug_redirects WHERE conference_id = ? AND old_slug = ?', [$conferenceId, $oldSlug]);
        $this->db->insert('slug_redirects', ['conference_id' => $conferenceId, 'old_slug' => $oldSlug, 'page_id' => $pageId]);
    }

    /** @return array{published: int, draft: int, hidden: int} */
    public function countByStatus(int $conferenceId): array
    {
        $counts = ['published' => 0, 'draft' => 0, 'hidden' => 0];
        foreach ($this->db->query('SELECT status, COUNT(*) AS n FROM pages WHERE conference_id = ? AND deleted_at IS NULL GROUP BY status', [$conferenceId]) as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        return $counts;
    }

    /** @return list<string> nhãn các mục menu (chưa xoá) đang trỏ tới trang */
    public function menuLabelsPointingTo(int $pageId): array
    {
        return array_column($this->db->query('SELECT label FROM menu_items WHERE page_id = ? AND deleted_at IS NULL ORDER BY label', [$pageId]), 'label');
    }

    /**
     * @param list<int> $pageIds
     * @return array<int, list<string>> id trang => danh sách thẻ
     */
    public function tagsFor(array $pageIds): array
    {
        if ($pageIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
        $tags = [];
        foreach ($this->db->query("SELECT page_id, tag FROM page_tags WHERE page_id IN ($placeholders) ORDER BY tag", $pageIds) as $row) {
            $tags[(int) $row['page_id']][] = $row['tag'];
        }

        return $tags;
    }
}
