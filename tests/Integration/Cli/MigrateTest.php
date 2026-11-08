<?php

declare(strict_types=1);

namespace Tests\Integration\Cli;

use App\Core\Migrator;
use Tests\Support\DatabaseTestCase;

/** Kiểm tra bin/migrate.php và lớp Migrator trên CSDL test. */
final class MigrateTest extends DatabaseTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/migrate-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
        parent::tearDown();
    }

    public function test_applies_files_in_name_order_and_only_once(): void
    {
        $table = 'zz_migrate_' . bin2hex(random_bytes(3));
        file_put_contents($this->dir . '/002_second.sql', "INSERT INTO $table (v) VALUES (N'hai');");
        file_put_contents($this->dir . '/001_first.sql', "CREATE TABLE $table (v NVARCHAR(10));\nGO\nINSERT INTO $table (v) VALUES (N'một');");
        $migrator = new Migrator($this->db, $this->dir);

        $first = $migrator->run();
        $second = $migrator->run();

        self::assertSame(['001_first.sql', '002_second.sql'], $first);
        self::assertSame([], $second, 'Chạy lần hai không áp dụng lại');
        self::assertSame(['một', 'hai'], array_column($this->db->query("SELECT v FROM $table"), 'v'));
        self::assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM schema_migrations WHERE filename = ?', ['001_first.sql']));
    }

    public function test_failing_file_is_rolled_back_and_stops(): void
    {
        $table = 'zz_migrate_' . bin2hex(random_bytes(3));
        file_put_contents($this->dir . '/001_ok.sql', "CREATE TABLE $table (v INT);");
        file_put_contents($this->dir . '/002_bad.sql', "INSERT INTO $table (v) VALUES (1);\nGO\nTHIS IS NOT SQL;");
        file_put_contents($this->dir . '/003_never.sql', "INSERT INTO $table (v) VALUES (3);");

        try {
            (new Migrator($this->db, $this->dir))->run();
            self::fail('Phải báo lỗi');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('002_bad.sql', $e->getMessage());
        }

        self::assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM $table"), 'Tệp lỗi phải được rollback');
        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM schema_migrations WHERE filename IN (?, ?)', ['002_bad.sql', '003_never.sql']));
    }

    public function test_cli_exits_with_error_code_when_a_file_fails(): void
    {
        file_put_contents($this->dir . '/001_bad.sql', 'THIS IS NOT SQL;');

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/migrate.php')
            . ' --test --dir=' . escapeshellarg($this->dir) . ' 2>&1', $output, $exitCode);

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('LỖI', implode("\n", $output));
    }

    public function test_cli_reports_up_to_date_on_real_migrations(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/migrate.php') . ' --test 2>&1', $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
    }
}
