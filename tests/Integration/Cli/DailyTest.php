<?php

declare(strict_types=1);

namespace Tests\Integration\Cli;

use App\Core\AppFactory;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Chạy thật bin/daily.php trên CSDL test (tiến trình riêng, dữ liệu được ghi hẳn; test tự dọn khi xong).
 * Sao lưu chạy ở chế độ thử (--dry-run-backup): không chạy lệnh BACKUP DATABASE thật.
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
    private function runDaily(string $backupDir): array
    {
        // Tiến trình con kế thừa biến môi trường; biến này được ưu tiên hơn giá trị trong .env
        $previous = getenv('BACKUP_DIR');
        putenv('BACKUP_DIR=' . $backupDir);
        try {
            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/daily.php') . ' --test --dry-run-backup';
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

    public function test_backup_failure_still_purges_and_exits_with_error(): void
    {
        $expired = $this->deletedDate('Quá hạn', 40);

        [$exitCode, $output] = $this->runDaily($this->backupDir . DIRECTORY_SEPARATOR . 'khong-ton-tai');

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('LỖI SAO LƯU', $output);
        self::assertNull($this->db->first('SELECT id FROM important_dates WHERE id = ?', [$expired]));
    }
}
