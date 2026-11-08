<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use App\Services\ActivityLogger;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class TrashActivityTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-31 00:00:00');
        $this->conferenceId = $this->createCurrentConference();
    }

    public function test_editor_sees_restore_but_not_permanent_delete(): void
    {
        $page = $this->createPage($this->conferenceId, ['title' => 'Trang trong thùng rác', 'deleted_at' => '2026-03-20 00:00:00']);
        $this->loginAs('editor');

        $this->get('/admin/trash');
        $this->assertStatus(200);
        $this->assertSee('Trang trong thùng rác');
        $this->assertSee('Khôi phục');
        $this->assertSee('19/04/2026'); // ngày tự xoá vĩnh viễn
        $this->assertDontSee('Xoá vĩnh viễn');

        $this->post("/admin/trash/pages/$page/purge");
        $this->assertStatus(403);
    }

    public function test_restore_page_and_handle_slug_conflict(): void
    {
        $old = $this->createPage($this->conferenceId, ['slug' => 'program', 'title' => 'Program cũ', 'deleted_at' => '2026-03-20 00:00:00']);
        $this->createPage($this->conferenceId, ['slug' => 'program', 'title' => 'Program mới']);
        $this->loginAs('editor');

        $this->post("/admin/trash/pages/$old/restore");
        $this->assertStatus(409);
        $this->assertSee('Đường dẫn của trang này đã được trang khác dùng.');

        $this->post("/admin/trash/pages/$old/restore", ['slug' => 'program-old']);
        $this->assertRedirect('/admin/trash');
        self::assertNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$old]));
        self::assertSame('program-old', $this->db->value('SELECT slug FROM pages WHERE id = ?', [$old]));
    }

    public function test_admin_can_purge(): void
    {
        $date = $this->createDate($this->conferenceId, ['label' => 'Mốc bỏ', 'deleted_at' => '2026-03-20 00:00:00']);
        $this->loginAs('admin');

        $this->get('/admin/trash');
        $this->assertSee('Xoá vĩnh viễn');
        $this->post("/admin/trash/important_dates/$date/purge");

        $this->assertRedirect('/admin/trash');
        self::assertNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$date]));
    }

    public function test_activity_log_is_admin_only_and_filterable(): void
    {
        $logger = new ActivityLogger($this->db);
        $logger->log(null, 'publish', 'pages', 1, 'Trang được xuất bản', $this->conferenceId);
        $logger->log(null, 'delete', 'pages', 2, 'Trang bị xoá', $this->conferenceId);

        $this->loginAs('editor');
        $this->get('/admin/activity');
        $this->assertStatus(403);

        $this->loginAs('admin');
        $this->get('/admin/activity');
        $this->assertStatus(200);
        $this->assertSee('Trang được xuất bản');
        $this->assertSee('31/03/2026');

        $this->get('/admin/activity?action=delete');
        $this->assertSee('Trang bị xoá');
        $this->assertDontSee('Trang được xuất bản');

        $this->get('/admin/activity?from=2026-04-01');
        $this->assertDontSee('Trang bị xoá');
    }

    public function test_activity_log_has_no_edit_or_delete_routes(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/activity/1/delete');
        $this->assertStatus(404);
        $this->get('/admin/activity/1');
        $this->assertStatus(404);
    }
}
