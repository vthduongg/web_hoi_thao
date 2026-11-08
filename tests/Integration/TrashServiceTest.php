<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Services\ActivityLogger;
use App\Services\PurgeRefused;
use App\Services\SlugConflict;
use App\Services\TrashService;
use Tests\Support\CreatesContent;
use Tests\Support\DatabaseTestCase;

final class TrashServiceTest extends DatabaseTestCase
{
    use CreatesContent;

    private TrashService $trash;
    private int $conferenceId;
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-31 00:00:00');
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $this->publicDir = sys_get_temp_dir() . '/trash-public-' . bin2hex(random_bytes(4));
        mkdir($this->publicDir . '/uploads/t', 0775, true);
        $this->trash = new TrashService($this->db, new ActivityLogger($this->db), $this->publicDir);
        $this->conferenceId = $this->createConference();
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->publicDir . '/uploads/t/*') ?: []);
        @rmdir($this->publicDir . '/uploads/t');
        @rmdir($this->publicDir . '/uploads');
        @rmdir($this->publicDir);
        parent::tearDown();
    }

    public function test_lists_deleted_items_of_conference_with_purge_date(): void
    {
        $page = $this->createPage($this->conferenceId, ['title' => 'Trang cũ', 'deleted_at' => '2026-03-20 10:00:00']);
        $date = $this->createDate($this->conferenceId, ['label' => 'Mốc cũ', 'deleted_at' => '2026-03-25 10:00:00']);
        $this->createPage($this->conferenceId, ['title' => 'Trang còn dùng']);
        $this->createPage($this->createConference(), ['title' => 'Trang hội thảo khác', 'deleted_at' => '2026-03-20 10:00:00']);
        $media = $this->createMedia(['original_name' => 'ảnh.jpg', 'deleted_at' => '2026-03-01 00:00:00']);

        $items = $this->trash->listForConference($this->conferenceId);

        $byKey = array_column($items, null, 'key');
        self::assertArrayHasKey("pages:$page", $byKey);
        self::assertArrayHasKey("important_dates:$date", $byKey);
        self::assertArrayHasKey("media_files:$media", $byKey);
        self::assertCount(3, $items);
        self::assertSame('Trang cũ', $byKey["pages:$page"]['label']);
        self::assertSame('Trang nội dung', $byKey["pages:$page"]['type_label']);
        self::assertStringStartsWith('2026-04-19 10:00:00', $byKey["pages:$page"]['purge_at']);
    }

    public function test_restore_clears_deleted_at_and_keeps_order(): void
    {
        $date = $this->createDate($this->conferenceId, ['sort_order' => 30, 'deleted_at' => '2026-03-25 00:00:00', 'deleted_by' => null]);

        $this->trash->restore('important_dates', $date, null);

        $row = $this->db->first('SELECT deleted_at, sort_order FROM important_dates WHERE id = ?', [$date]);
        self::assertNull($row['deleted_at']);
        self::assertSame(30, $row['sort_order']);
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'restore' AND entity_id = ?", [$date]));
    }

    public function test_restoring_page_whose_slug_is_taken_requires_new_slug(): void
    {
        $old = $this->createPage($this->conferenceId, ['slug' => 'program', 'deleted_at' => '2026-03-25 00:00:00']);
        $this->createPage($this->conferenceId, ['slug' => 'program']);

        try {
            $this->trash->restore('pages', $old, null);
            self::fail('Phải yêu cầu slug khác');
        } catch (SlugConflict) {
        }

        $this->trash->restore('pages', $old, null, 'program-old');
        self::assertSame('program-old', $this->db->value('SELECT slug FROM pages WHERE id = ?', [$old]));
        self::assertNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$old]));
    }

    public function test_purge_expired_removes_only_items_older_than_30_days(): void
    {
        $expired = $this->createDate($this->conferenceId, ['label' => 'Quá hạn', 'deleted_at' => '2026-02-28 23:59:59']);
        $recent = $this->createDate($this->conferenceId, ['label' => 'Còn hạn', 'deleted_at' => '2026-03-02 00:00:00']);

        $count = $this->trash->purgeExpired();

        self::assertGreaterThanOrEqual(1, $count);
        self::assertNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$expired]));
        self::assertNotNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$recent]));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'purge' AND entity_id = ?", [$expired]));
    }

    public function test_purging_committee_removes_its_members(): void
    {
        $committee = $this->db->insert('committees', ['conference_id' => $this->conferenceId, 'name' => 'Ban xoá', 'sort_order' => 10, 'deleted_at' => '2026-03-01']);
        $this->db->insert('committee_members', ['committee_id' => $committee, 'full_name' => 'A', 'sort_order' => 10]);

        $this->trash->purge('committees', $committee, null);

        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM committee_members WHERE committee_id = ?', [$committee]));
    }

    public function test_purging_media_deletes_files_and_refuses_when_still_used(): void
    {
        file_put_contents($this->publicDir . '/uploads/t/a.jpg', 'x');
        file_put_contents($this->publicDir . '/uploads/t/a-400.jpg', 'x');
        $free = $this->createMedia(['stored_path' => 'uploads/t/a.jpg', 'thumb_small_path' => 'uploads/t/a-400.jpg', 'deleted_at' => '2026-03-01']);
        $used = $this->createMedia(['stored_path' => 'uploads/t/b.jpg', 'deleted_at' => '2026-03-01']);
        $this->db->insert('slides', ['conference_id' => $this->conferenceId, 'media_id' => $used, 'alt_text' => 'b', 'sort_order' => 10]);

        $this->trash->purge('media_files', $free, null);
        self::assertFileDoesNotExist($this->publicDir . '/uploads/t/a.jpg');
        self::assertFileDoesNotExist($this->publicDir . '/uploads/t/a-400.jpg');
        self::assertNull($this->db->first('SELECT id FROM media_files WHERE id = ?', [$free]));

        $this->expectException(PurgeRefused::class);
        $this->trash->purge('media_files', $used, null);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->trash->restore('users', 1, null);
    }
}
