<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;

/**
 * Hàm tạo nhanh dữ liệu mẫu trong test (dùng trong lớp con của DatabaseTestCase).
 *
 * @property Database $db
 */
trait CreatesContent
{
    /** Tạo hội thảo hiện hành (bỏ cờ hiện hành của hội thảo khác trong transaction test). */
    protected function createCurrentConference(array $overrides = []): int
    {
        $this->db->execute('UPDATE conferences SET is_current = 0 WHERE is_current = 1');

        return $this->createConference(array_merge(['is_current' => 1, 'has_been_current' => 1], $overrides));
    }

    protected function createDate(int $conferenceId, array $overrides = []): int
    {
        return $this->db->insert('important_dates', array_merge([
            'conference_id' => $conferenceId,
            'label' => 'Milestone',
            'status' => 'normal',
            'date_start' => '2026-06-01',
            'date_end' => null,
            'original_date' => null,
            'special_role' => null,
            'sort_order' => 10,
            'is_visible' => 1,
        ], $overrides));
    }

    protected function createPage(int $conferenceId, array $overrides = []): int
    {
        return $this->db->insert('pages', array_merge([
            'conference_id' => $conferenceId,
            'title' => 'Page ' . bin2hex(random_bytes(3)),
            'slug' => 'page-' . bin2hex(random_bytes(3)),
            'body_html' => '<p>Body text of the page.</p>',
            'module' => 'none',
            'status' => 'published',
            'published_at' => '2020-01-01 00:00:00',
            'version' => 1,
        ], $overrides));
    }

    protected function tagPage(int $pageId, string ...$tags): void
    {
        foreach ($tags as $tag) {
            $this->db->insertRow('page_tags', ['page_id' => $pageId, 'tag' => $tag]);
        }
    }

    protected function createMenuItem(int $conferenceId, array $overrides = []): int
    {
        return $this->db->insert('menu_items', array_merge([
            'conference_id' => $conferenceId,
            'parent_id' => null,
            'label' => 'Menu item',
            'page_id' => null,
            'external_url' => null,
            'sort_order' => 10,
        ], $overrides));
    }

    protected function createMedia(array $overrides = []): int
    {
        return $this->db->insert('media_files', array_merge([
            'original_name' => 'anh.jpg',
            'stored_path' => 'uploads/test/anh.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1000,
            'kind' => 'image',
            'width' => 800,
            'height' => 400,
        ], $overrides));
    }
}
