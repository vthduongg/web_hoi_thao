<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use App\Services\ActivityLogger;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class DashboardTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 01:00:00');
        $this->conferenceId = $this->createCurrentConference();
    }

    public function test_editor_and_admin_can_open_dashboard(): void
    {
        foreach (['editor', 'admin'] as $role) {
            $this->loginAs($role);
            $this->get('/admin');
            $this->assertStatus(200);
            $this->assertSee('Bảng điều khiển');
        }
    }

    public function test_shows_page_counts(): void
    {
        $this->createPage($this->conferenceId);
        $this->createPage($this->conferenceId);
        $this->createPage($this->conferenceId, ['status' => 'draft', 'published_at' => null]);
        $this->createPage($this->createConference()); // hội thảo khác không tính
        $this->loginAs('editor');

        $this->get('/admin');

        $this->assertSee('data-count="published">2<');
        $this->assertSee('data-count="draft">1<');
    }

    public function test_shows_five_upcoming_dates(): void
    {
        foreach (['2026-03-01', '2026-03-11', '2026-03-12', '2026-03-13', '2026-03-14', '2026-03-15', '2026-03-16'] as $i => $day) {
            $this->createDate($this->conferenceId, ['label' => "Mốc $i", 'date_start' => $day, 'sort_order' => $i]);
        }
        $this->loginAs('editor');

        $this->get('/admin');

        $this->assertDontSee('Mốc 0'); // đã qua
        foreach ([1, 2, 3, 4, 5] as $i) {
            $this->assertSee("Mốc $i");
        }
        $this->assertDontSee('Mốc 6'); // chỉ 5 mốc
        $this->assertSee('11/03/2026');
    }

    public function test_recent_changes_only_content_actions_of_editing_conference_without_ip(): void
    {
        $logger = new ActivityLogger($this->db);
        $logger->setIpAddress('203.0.113.77');
        $other = $this->createConference();
        $logger->log(null, 'update', 'pages', 1, 'Trang được sửa', $this->conferenceId);
        $logger->log(null, 'publish', 'pages', 2, 'Trang được xuất bản', $this->conferenceId);
        $logger->log(null, 'update', 'pages', 3, 'Trang của hội thảo khác', $other);
        foreach (['login', 'logout', 'login_failed', 'account_change'] as $action) {
            $logger->log(null, $action, 'users', 9, "Sự kiện $action", $this->conferenceId);
        }
        for ($i = 0; $i < 12; $i++) {
            $logger->log(null, 'create', 'pages', 100 + $i, "Trang mới $i", $this->conferenceId);
        }
        $this->loginAs('editor');

        $body = $this->get('/admin')->body;

        $this->assertSee('Trang mới 11');
        $this->assertDontSee('Trang mới 1<'); // chỉ 10 dòng mới nhất
        $this->assertDontSee('Trang của hội thảo khác');
        $this->assertDontSee('203.0.113.77');
        foreach (['login', 'logout', 'login_failed', 'account_change'] as $action) {
            $this->assertDontSee("Sự kiện $action");
        }
        self::assertSame(10, substr_count($body, 'data-change-row'));
    }
}
