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
