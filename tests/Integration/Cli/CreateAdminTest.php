<?php

declare(strict_types=1);

namespace Tests\Integration\Cli;

use App\Core\AppFactory;
use App\Core\Database;
use PHPUnit\Framework\TestCase;

/**
 * Chạy thật bin/create-admin.php trên CSDL test (tiến trình riêng nên dữ liệu được ghi hẳn;
 * test tự xoá tài khoản đã tạo khi xong).
 */
final class CreateAdminTest extends TestCase
{
    private Database $db;
    private string $email;

    protected function setUp(): void
    {
        $this->db = AppFactory::connectDatabase(useTestDatabase: true, createIfMissing: true);
        $this->email = 'cli-' . bin2hex(random_bytes(4)) . '@example.com';
    }

    protected function tearDown(): void
    {
        $this->db->execute('DELETE FROM activity_logs WHERE entity_type = ? AND entity_id IN (SELECT id FROM users WHERE email = ?)', ['users', $this->email]);
        $this->db->execute('DELETE FROM users WHERE email = ?', [$this->email]);
    }

    /** @return array{0: int, 1: string} mã thoát, nội dung in ra */
    private function runCli(string ...$args): array
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/create-admin.php') . ' --test';
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }
        exec($command . ' 2>&1', $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }

    public function test_creates_active_admin_and_prints_working_temporary_password(): void
    {
        [$exitCode, $output] = $this->runCli($this->email, 'Quản trị viên CLI');

        self::assertSame(0, $exitCode, $output);
        self::assertMatchesRegularExpression('/Mật khẩu tạm: (\S{12})$/m', $output);
        preg_match('/Mật khẩu tạm: (\S{12})$/m', $output, $m);
        $user = $this->db->first('SELECT * FROM users WHERE email = ?', [$this->email]);
        self::assertSame('admin', $user['role']);
        self::assertSame(1, (int) $user['is_active']);
        self::assertSame('Quản trị viên CLI', $user['full_name']);
        self::assertTrue(password_verify($m[1], $user['password_hash']));
    }

    public function test_duplicate_email_fails_with_vietnamese_message(): void
    {
        $this->runCli($this->email, 'Lần một');

        [$exitCode, $output] = $this->runCli($this->email, 'Lần hai');

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('Email này đã được dùng.', $output);
    }

    public function test_missing_arguments_print_usage(): void
    {
        [$exitCode, $output] = $this->runCli();

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('Cách dùng: php bin/create-admin.php', $output);
    }
}
