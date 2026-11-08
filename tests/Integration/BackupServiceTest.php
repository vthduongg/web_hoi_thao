<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Services\BackupFailed;
use App\Services\BackupService;
use Tests\Support\DatabaseTestCase;

final class BackupServiceTest extends DatabaseTestCase
{
    private string $publicDir;
    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 19:00:00'); // 02:00 ngày 11/03 giờ VN
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $base = sys_get_temp_dir() . '/backup-test-' . bin2hex(random_bytes(4));
        $this->publicDir = "$base/public";
        $this->backupDir = "$base/backups";
        mkdir($this->publicDir . '/uploads/2026/03', 0775, true);
        mkdir($this->backupDir, 0775, true);
        file_put_contents($this->publicDir . '/uploads/2026/03/photo.jpg', 'image-bytes');
    }

    protected function tearDown(): void
    {
        $base = dirname($this->publicDir);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($base);
        parent::tearDown();
    }

    private function service(): BackupService
    {
        return new BackupService($this->db, 'conference_test', $this->publicDir, $this->backupDir, dryRun: true);
    }

    public function test_builds_backup_command_with_vietnam_date(): void
    {
        $service = $this->service();

        $service->run();

        self::assertSame(
            ["BACKUP DATABASE [conference_test] TO DISK = N'" . $this->backupDir . DIRECTORY_SEPARATOR . "conference-20260311.bak' WITH INIT, COPY_ONLY"],
            $service->executedCommands,
        );
    }

    public function test_zips_uploads_folder(): void
    {
        $this->service()->run();

        $zipPath = $this->backupDir . '/uploads-20260311.zip';
        self::assertFileExists($zipPath);
        $zip = new \ZipArchive();
        $zip->open($zipPath);
        self::assertSame('image-bytes', $zip->getFromName('uploads/2026/03/photo.jpg'));
        $zip->close();
    }

    public function test_keeps_only_the_30_newest_backups_of_each_kind(): void
    {
        for ($day = 1; $day <= 32; $day++) {
            $date = sprintf('202601%02d', $day);
            touch("{$this->backupDir}/conference-$date.bak");
            touch("{$this->backupDir}/uploads-$date.zip");
        }

        $this->service()->run();

        $zips = glob($this->backupDir . '/uploads-*.zip');
        self::assertCount(30, $zips);
        self::assertFileDoesNotExist($this->backupDir . '/uploads-20260101.zip');
        self::assertFileDoesNotExist($this->backupDir . '/uploads-20260103.zip');
        self::assertFileExists($this->backupDir . '/uploads-20260311.zip');
        self::assertCount(30, glob($this->backupDir . '/conference-*.bak'));
        self::assertFileDoesNotExist($this->backupDir . '/conference-20260102.bak');
    }

    public function test_missing_backup_folder_gives_clear_error(): void
    {
        $service = new BackupService($this->db, 'conference_test', $this->publicDir, $this->backupDir . '/khong-ton-tai', dryRun: true);

        $this->expectException(BackupFailed::class);
        $this->expectExceptionMessage('Thư mục sao lưu không tồn tại hoặc không ghi được');
        $service->run();
    }
}
