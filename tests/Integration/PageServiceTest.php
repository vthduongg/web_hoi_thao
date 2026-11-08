<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Services\ActivityLogger;
use App\Services\EditConflict;
use App\Services\HtmlSanitizer;
use App\Services\PageService;
use Tests\Support\CreatesContent;
use Tests\Support\DatabaseTestCase;

final class PageServiceTest extends DatabaseTestCase
{
    use CreatesContent;

    private PageService $service;
    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 08:00:00');
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $this->service = new PageService($this->db, new HtmlSanitizer(sys_get_temp_dir() . '/htmlpurifier-test'), new ActivityLogger($this->db));
        $this->conferenceId = $this->createConference();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Call for Papers',
            'slug' => 'call-for-papers',
            'body_html' => '<p>Nội dung</p>',
            'module' => 'none',
            'seo_title' => '',
            'seo_description' => '',
            'featured_media_id' => null,
            'tags' => ['deadline'],
            'status' => 'draft',
            'published_at' => null,
        ], $overrides);
    }

    public function test_create_saves_page_first_revision_and_tags(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);

        $page = $this->db->first('SELECT * FROM pages WHERE id = ?', [$id]);
        self::assertSame('Call for Papers', $page['title']);
        self::assertSame(1, $page['version']);
        self::assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM page_revisions WHERE page_id = ?', [$id]));
        self::assertSame(['deadline'], array_column($this->db->query('SELECT tag FROM page_tags WHERE page_id = ?', [$id]), 'tag'));
    }

    public function test_body_is_sanitized(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(['body_html' => '<p onclick="x()">Hi</p><script>alert(1)</script>']), null);

        self::assertSame('<p>Hi</p>', $this->db->value('SELECT body_html FROM pages WHERE id = ?', [$id]));
    }

    public function test_pasted_youtube_link_is_saved_as_responsive_iframe(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(['body_html' => '<p>https://youtu.be/abc123</p>']), null);

        $body = (string) $this->db->value('SELECT body_html FROM pages WHERE id = ?', [$id]);
        self::assertStringContainsString('<div class="embed-responsive"><iframe src="https://www.youtube-nocookie.com/embed/abc123"', $body);
    }

    public function test_update_creates_new_revision_and_increments_version(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);

        $this->service->update($id, $this->data(['title' => 'CFP mới', 'tags' => ['a', 'b']]), 1, null);

        self::assertSame('CFP mới', $this->db->value('SELECT title FROM pages WHERE id = ?', [$id]));
        self::assertSame(2, (int) $this->db->value('SELECT version FROM pages WHERE id = ?', [$id]));
        self::assertSame([1, 2], array_column($this->db->query('SELECT revision_no FROM page_revisions WHERE page_id = ? ORDER BY revision_no', [$id]), 'revision_no'));
        self::assertSame(['a', 'b'], array_column($this->db->query('SELECT tag FROM page_tags WHERE page_id = ? ORDER BY tag', [$id]), 'tag'));
    }

    public function test_stale_version_throws_conflict_and_writes_nothing(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);
        $this->service->update($id, $this->data(['title' => 'Bản của người A']), 1, null);

        try {
            $this->service->update($id, $this->data(['title' => 'Bản của người B']), 1, null);
            self::fail('Phải báo xung đột');
        } catch (EditConflict) {
        }

        self::assertSame('Bản của người A', $this->db->value('SELECT title FROM pages WHERE id = ?', [$id]));
        self::assertSame(2, (int) $this->db->value('SELECT COUNT(*) FROM page_revisions WHERE page_id = ?', [$id]));
    }

    public function test_changing_slug_of_published_page_creates_redirect(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(['status' => 'published']), null);

        $this->service->update($id, $this->data(['status' => 'published', 'slug' => 'cfp']), 1, null);

        self::assertSame($id, (int) $this->db->value('SELECT page_id FROM slug_redirects WHERE conference_id = ? AND old_slug = ?', [$this->conferenceId, 'call-for-papers']));
    }

    public function test_changing_slug_of_draft_does_not_create_redirect(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);

        $this->service->update($id, $this->data(['slug' => 'cfp']), 1, null);

        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM slug_redirects WHERE page_id = ?', [$id]));
    }

    public function test_restore_revision_copies_old_content_as_new_revision(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(['body_html' => '<p>Bản 1</p>']), null);
        $this->service->update($id, $this->data(['body_html' => '<p>Bản 2</p>']), 1, null);

        $this->service->restoreRevision($id, 1, null);

        self::assertSame('<p>Bản 1</p>', $this->db->value('SELECT body_html FROM pages WHERE id = ?', [$id]));
        $revisions = $this->db->query('SELECT revision_no, body_html FROM page_revisions WHERE page_id = ? ORDER BY revision_no', [$id]);
        self::assertSame([[1, '<p>Bản 1</p>'], [2, '<p>Bản 2</p>'], [3, '<p>Bản 1</p>']], array_map(static fn ($r) => [$r['revision_no'], $r['body_html']], $revisions));
    }

    public function test_publish_without_time_publishes_now_and_logs(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);

        $this->service->changeStatus($id, 'published', null, null);

        $page = $this->db->first('SELECT status, published_at FROM pages WHERE id = ?', [$id]);
        self::assertSame('published', $page['status']);
        self::assertStringStartsWith('2026-03-10 08:00:00', (string) $page['published_at']);
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'publish' AND entity_id = ?", [$id]));
    }

    public function test_validate_reports_duplicate_and_invalid_slug(): void
    {
        $this->service->create($this->conferenceId, $this->data(), null);

        $errors = $this->service->validate($this->conferenceId, $this->data(['title' => '']), null);
        self::assertArrayHasKey('slug', $errors);
        self::assertArrayHasKey('title', $errors);

        self::assertArrayHasKey('slug', $this->service->validate($this->conferenceId, $this->data(['slug' => 'admin']), null));
        self::assertArrayHasKey('published_at', $this->service->validate($this->conferenceId, $this->data(['published_at' => 'hôm nay']), null));
        self::assertSame([], $this->service->validate($this->createConference(), $this->data(), null), 'Slug trùng ở hội thảo khác thì được');
    }

    public function test_soft_delete_and_logs(): void
    {
        $id = $this->service->create($this->conferenceId, $this->data(), null);

        $this->service->softDelete($id, null);

        self::assertNotNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$id]));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'delete' AND entity_id = ?", [$id]));
    }
}
