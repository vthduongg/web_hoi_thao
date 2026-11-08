<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class PagesTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 01:00:00'); // 08:00 giờ VN
        $this->conferenceId = $this->createCurrentConference(['name' => 'ELT 2026']);
        $this->loginAs('editor');
    }

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Paper Submission',
            'slug' => 'paper-submission',
            'body_html' => '<p>Submit your full paper.</p>',
            'module' => 'none',
            'seo_title' => '',
            'seo_description' => '',
            'featured_media_id' => '',
            'tags' => 'submission, deadline',
            'status' => 'draft',
            'published_at' => '',
        ], $overrides);
    }

    private function pageId(string $slug): int
    {
        return (int) $this->db->value('SELECT id FROM pages WHERE conference_id = ? AND slug = ?', [$this->conferenceId, $slug]);
    }

    public function test_editor_creates_draft_that_is_not_public(): void
    {
        $this->get('/admin/pages/new');
        $this->assertStatus(200);

        $this->post('/admin/pages/new', $this->formData());

        $id = $this->pageId('paper-submission');
        $this->assertRedirect("/admin/pages/$id");
        $this->get('/paper-submission');
        $this->assertStatus(404);
    }

    public function test_publish_makes_page_public_immediately(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');

        $this->post("/admin/pages/$id/publish", ['published_at' => '']);

        $this->assertRedirect("/admin/pages/$id");
        $this->get('/paper-submission');
        $this->assertStatus(200);
        $this->assertSee('Submit your full paper.');
    }

    public function test_hide_and_back_to_draft(): void
    {
        $this->post('/admin/pages/new', $this->formData(['status' => 'published']));
        $id = $this->pageId('paper-submission');

        $this->post("/admin/pages/$id/hide");
        $this->get('/paper-submission');
        $this->assertStatus(404);
        self::assertSame('hidden', $this->db->value('SELECT status FROM pages WHERE id = ?', [$id]));

        $this->post("/admin/pages/$id/draft");
        self::assertSame('draft', $this->db->value('SELECT status FROM pages WHERE id = ?', [$id]));
    }

    public function test_scheduled_page_appears_at_publish_time(): void
    {
        $this->post('/admin/pages/new', $this->formData(['status' => 'published', 'published_at' => '2026-03-10T09:00'])); // giờ VN

        $this->get('/paper-submission');
        $this->assertStatus(404);

        Clock::freeze('2026-03-10 02:00:00'); // 09:00 giờ VN
        $this->get('/paper-submission');
        $this->assertStatus(200);
    }

    public function test_edit_updates_content(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');

        $this->get("/admin/pages/$id");
        $this->assertStatus(200);
        $this->assertSee('value="1"'); // phiên bản hiện tại gửi kèm biểu mẫu
        $this->post("/admin/pages/$id", $this->formData(['title' => 'Nộp bài', 'version' => '1']));

        $this->assertRedirect("/admin/pages/$id");
        self::assertSame('Nộp bài', $this->db->value('SELECT title FROM pages WHERE id = ?', [$id]));
    }

    public function test_duplicate_slug_shows_vietnamese_error(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'paper-submission']);

        $this->post('/admin/pages/new', $this->formData());

        $this->assertStatus(422);
        $this->assertSee('Đường dẫn này đã được trang khác dùng.');
    }

    public function test_reserved_or_invalid_slug_rejected(): void
    {
        $this->post('/admin/pages/new', $this->formData(['slug' => 'admin']));
        $this->assertStatus(422);
        $this->assertSee('Đường dẫn này được hệ thống dùng, vui lòng chọn đường dẫn khác.');

        $this->post('/admin/pages/new', $this->formData(['slug' => 'Có Dấu']));
        $this->assertStatus(422);
        $this->assertSee('Đường dẫn chỉ gồm chữ thường không dấu, số và dấu gạch ngang.');
    }

    public function test_edit_conflict_shows_warning_and_keeps_other_version(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');
        $this->post("/admin/pages/$id", $this->formData(['title' => 'Bản tab 1', 'version' => '1']));

        $this->post("/admin/pages/$id", $this->formData(['title' => 'Bản tab 2', 'version' => '1']));

        $this->assertStatus(409);
        $this->assertSee('Trang đã được người khác sửa trong lúc bạn đang sửa.');
        self::assertSame('Bản tab 1', $this->db->value('SELECT title FROM pages WHERE id = ?', [$id]));
    }

    public function test_preview_shows_draft_with_banner(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');

        $this->get("/admin/pages/$id/preview");

        $this->assertStatus(200);
        $this->assertSee('Bản xem trước');
        $this->assertSee('Submit your full paper.');
        $this->assertSee('<html lang="en">');
    }

    public function test_preview_requires_login(): void
    {
        $id = $this->createPage($this->conferenceId, ['status' => 'draft', 'published_at' => null]);
        $this->post('/admin/logout');

        $this->get("/admin/pages/$id/preview");

        $this->assertRedirect('/admin/login?next=' . rawurlencode("/admin/pages/$id/preview"));
    }

    public function test_revisions_list_and_restore(): void
    {
        $this->post('/admin/pages/new', $this->formData(['body_html' => '<p>Bản đầu</p>']));
        $id = $this->pageId('paper-submission');
        $this->post("/admin/pages/$id", $this->formData(['body_html' => '<p>Bản sau</p>', 'version' => '1']));

        $this->get("/admin/pages/$id/revisions");
        $this->assertStatus(200);
        $this->post("/admin/pages/$id/revisions/1/restore");

        $this->assertRedirect("/admin/pages/$id");
        self::assertSame('<p>Bản đầu</p>', $this->db->value('SELECT body_html FROM pages WHERE id = ?', [$id]));
    }

    public function test_list_filters_by_status_tag_and_keyword(): void
    {
        $a = $this->createPage($this->conferenceId, ['title' => 'Travel Info', 'slug' => 'travel']);
        $this->createPage($this->conferenceId, ['title' => 'Draft Notes', 'slug' => 'notes', 'status' => 'draft', 'published_at' => null]);
        $this->tagPage($a, 'travel');

        $this->get('/admin/pages?status=draft');
        $this->assertSee('Draft Notes');
        $this->assertDontSee('Travel Info');

        $this->get('/admin/pages?tag=travel');
        $this->assertSee('Travel Info');
        $this->assertDontSee('Draft Notes');

        $this->get('/admin/pages?q=' . rawurlencode('notes'));
        $this->assertSee('Draft Notes');
        $this->assertDontSee('Travel Info');

        // Ký tự % trong từ khoá được hiểu đúng nghĩa đen
        $this->get('/admin/pages?q=' . rawurlencode('%'));
        $this->assertDontSee('Draft Notes');
        $this->assertDontSee('Travel Info');
    }

    public function test_publish_with_invalid_time_keeps_status_and_shows_error(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');

        $this->post("/admin/pages/$id/publish", ['published_at' => '31/12/2026']);

        $this->assertRedirect("/admin/pages/$id");
        self::assertSame('draft', $this->db->value('SELECT status FROM pages WHERE id = ?', [$id]));
        $this->get("/admin/pages/$id");
        $this->assertSee('Ngày không hợp lệ.');
    }

    public function test_restoring_missing_revision_returns_404(): void
    {
        $this->post('/admin/pages/new', $this->formData());
        $id = $this->pageId('paper-submission');

        $this->post("/admin/pages/$id/revisions/999/restore");

        $this->assertStatus(404);
    }

    public function test_edit_form_shows_publish_time_in_vietnamese_format(): void
    {
        $this->post('/admin/pages/new', $this->formData(['status' => 'published', 'published_at' => '2026-03-10T09:00']));

        $this->get('/admin/pages/' . $this->pageId('paper-submission'));

        $this->assertSee('Giá trị đã chọn: 10/03/2026 09:00');
    }

    public function test_pages_of_other_conferences_are_not_editable(): void
    {
        $other = $this->createPage($this->createConference());

        $this->get("/admin/pages/$other");

        $this->assertStatus(404);
    }
}
