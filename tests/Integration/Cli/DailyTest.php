<?php

declare(strict_types=1);

namespace Tests\Integration\Cli;

use App\Core\AppFactory;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Chạy thật bin/daily.php trên CSDL test (tiến trình riêng, dữ liệu được ghi hẳn; test tự dọn khi xong).
 * Hầu hết test sao lưu ở chế độ thử (--dry-run-backup); riêng test_real_backup_* chạy BACKUP DATABASE thật.
 */
final class DailyTest extends TestCase
{
    private Database $db;
    private int $conferenceId;
    private string $backupDir;

    protected function setUp(): void
    {
        $this->db = AppFactory::connectDatabase(useTestDatabase: true, createIfMissing: true);
        $this->conferenceId = $this->db->insert('conferences', ['name' => 'Daily test', 'year' => random_int(5000, 9999)]);
        $this->backupDir = sys_get_temp_dir() . '/daily-' . bin2hex(random_bytes(4));
        mkdir($this->backupDir);
    }

    protected function tearDown(): void
    {
        $this->db->execute('DELETE FROM important_dates WHERE conference_id = ?', [$this->conferenceId]);
        $this->db->execute('DELETE FROM activity_logs WHERE conference_id = ?', [$this->conferenceId]);
        $this->db->execute('DELETE FROM conferences WHERE id = ?', [$this->conferenceId]);
        array_map('unlink', glob($this->backupDir . '/*') ?: []);
        rmdir($this->backupDir);
    }

    private function deletedDate(string $label, int $daysAgo): int
    {
        return $this->db->insert('important_dates', [
            'conference_id' => $this->conferenceId, 'label' => $label, 'status' => 'normal', 'date_start' => '2027-01-01',
            'sort_order' => 10, 'deleted_at' => gmdate('Y-m-d H:i:s', time() - $daysAgo * 86400),
        ]);
    }

    /** @return array{0: int, 1: string} */
    private function runDaily(string $backupDir, bool $dryRunBackup = true): array
    {
        // Tiến trình con kế thừa biến môi trường; biến này được ưu tiên hơn giá trị trong .env
        $previous = getenv('BACKUP_DIR');
        putenv('BACKUP_DIR=' . $backupDir);
        try {
            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/daily.php') . ' --test' . ($dryRunBackup ? ' --dry-run-backup' : '');
            exec($command . ' 2>&1', $output, $exitCode);
        } finally {
            putenv($previous === false ? 'BACKUP_DIR' : 'BACKUP_DIR=' . $previous);
        }

        return [$exitCode, implode("\n", $output)];
    }

    public function test_purges_expired_trash_backs_up_and_logs(): void
    {
        $expired = $this->deletedDate('Quá hạn', 31);
        $recent = $this->deletedDate('Còn hạn', 29);

        [$exitCode, $output] = $this->runDaily($this->backupDir);

        self::assertSame(0, $exitCode, $output);
        self::assertNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$expired]));
        self::assertNotNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$recent]));
        self::assertNotEmpty(glob($this->backupDir . '/uploads-*.zip'));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'purge' AND entity_id = ?", [$expired]));
        self::assertGreaterThanOrEqual(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'backup' AND created_at > DATEADD(MINUTE, -5, SYSUTCDATETIME())"));
        self::assertStringContainsString('Đã xoá vĩnh viễn', $output);
    }

    /**
     * Sao lưu THẬT CSDL test vào thư mục con của BACKUP_DIR (thư mục mà tài khoản dịch vụ SQL Server ghi được,
     * xem docs/van-hanh.md). Test này bắt được lỗi "báo thành công nhưng không có tệp .bak".
     */
    public function test_real_backup_writes_bak_file_and_records_it_in_sql_server(): void
    {
        $base = \App\Core\Env::get('BACKUP_DIR', '');
        if ($base === '' || !is_dir($base) || !is_writable($base)) {
            self::markTestSkipped('Cần BACKUP_DIR trong .env trỏ tới thư mục có sẵn mà SQL Server ghi được (docs/van-hanh.md).');
        }
        $dir = $base . DIRECTORY_SEPARATOR . 'phpunit-' . bin2hex(random_bytes(4));
        mkdir($dir);

        try {
            [$exitCode, $output] = $this->runDaily($dir, dryRunBackup: false);

            self::assertSame(0, $exitCode, $output);
            $bak = glob($dir . '/conference-*.bak') ?: [];
            self::assertCount(1, $bak, $output);
            self::assertGreaterThan(0, filesize($bak[0]));
            self::assertSame(1, (int) $this->db->value(
                'SELECT COUNT(*) FROM msdb.dbo.backupset b JOIN msdb.dbo.backupmediafamily m ON m.media_set_id = b.media_set_id
                 WHERE m.physical_device_name = ? AND b.backup_finish_date IS NOT NULL',
                [realpath($bak[0])],
            ));
        } finally {
            array_map('unlink', glob($dir . '/*') ?: []);
            @rmdir($dir);
        }
    }

    public function test_backup_failure_still_purges_and_exits_with_error(): void
    {
        $expired = $this->deletedDate('Quá hạn', 40);

        [$exitCode, $output] = $this->runDaily($this->backupDir . DIRECTORY_SEPARATOR . 'khong-ton-tai');

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('LỖI SAO LƯU', $output);
        self::assertNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$expired]));
    }
}
