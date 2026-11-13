<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\VietEsl2025Data;
use App\Services\VietEsl2025Seeder;
use Tests\Support\DatabaseTestCase;

final class VietEsl2025SeederTest extends DatabaseTestCase
{
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicDir = sys_get_temp_dir() . '/vietesl-public-' . bin2hex(random_bytes(4));
        mkdir($this->publicDir);
        // CSDL test không được có sẵn hội thảo 2025 (mọi thay đổi trong test đều được rollback)
        $this->db->execute('UPDATE conferences SET year = year + 10000 WHERE year = ?', [VietEsl2025Data::YEAR]);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->publicDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->publicDir);
        parent::tearDown();
    }

    private function seed(): int
    {
        return (new VietEsl2025Seeder($this->db, dirname(__DIR__, 2), $this->publicDir))->run();
    }

    private function countFor(string $sql, int $conferenceId): int
    {
        return (int) $this->db->value($sql, [$conferenceId]);
    }

    public function test_loads_all_vietesl_content(): void
    {
        $id = $this->seed();

        self::assertSame('VietESL International Conference 2025', $this->db->value('SELECT name FROM conferences WHERE id = ?', [$id]));
        self::assertSame(8, $this->countFor('SELECT COUNT(*) FROM important_dates WHERE conference_id = ?', $id));
        self::assertSame(10, $this->countFor('SELECT COUNT(*) FROM pages WHERE conference_id = ?', $id));
        self::assertSame(11, $this->countFor('SELECT COUNT(*) FROM menu_items WHERE conference_id = ?', $id));
        self::assertSame(33, $this->countFor('SELECT COUNT(*) FROM committee_members m JOIN committees c ON c.id = m.committee_id WHERE c.conference_id = ?', $id));
        self::assertSame(3, $this->countFor('SELECT COUNT(*) FROM keynote_speakers WHERE conference_id = ?', $id));
        self::assertSame(24, $this->countFor(
            'SELECT COUNT(*) FROM program_items i JOIN program_sessions s ON s.id = i.program_session_id
             JOIN program_days d ON d.id = s.program_day_id WHERE d.conference_id = ?',
            $id,
        ));
        self::assertSame(3, $this->countFor('SELECT COUNT(*) FROM sponsors WHERE conference_id = ?', $id));
        self::assertSame(2, $this->countFor('SELECT COUNT(*) FROM contact_persons WHERE conference_id = ?', $id));
    }

    public function test_full_paper_deadline_is_extended_and_images_have_thumbnails(): void
    {
        $id = $this->seed();

        $deadline = $this->db->first("SELECT status, date_start, original_date FROM important_dates WHERE conference_id = ? AND special_role = 'full_paper_deadline'", [$id]);
        self::assertSame(['status' => 'extended', 'date_start' => '2025-11-05', 'original_date' => '2025-10-30'], $deadline);

        $logo = $this->db->first('SELECT m.stored_path, m.thumb_small_path FROM conferences c JOIN media_files m ON m.id = c.logo_media_id WHERE c.id = ?', [$id]);
        self::assertFileExists($this->publicDir . '/' . $logo['stored_path']);
        self::assertNotNull($logo['thumb_small_path']);
    }

    public function test_refuses_to_run_twice(): void
    {
        $this->seed();

        $this->expectException(\RuntimeException::class);
        $this->seed();
    }
}
