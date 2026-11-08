<?php

declare(strict_types=1);

namespace Tests\Integration\Cli;

use App\Services\Seeder;
use Tests\Support\DatabaseTestCase;

/**
 * Kiểm tra dữ liệu mẫu (bin/seed.php). Mỗi user story bổ sung assert cho dữ liệu mẫu của mình.
 */
final class SeedTest extends DatabaseTestCase
{
    private const TABLES = ['conferences', 'users'];

    private function seeder(): Seeder
    {
        return new Seeder($this->db, dirname(__DIR__, 3));
    }

    /** @return array<string, int> số dòng của từng bảng */
    private function counts(): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = (int) $this->db->value("SELECT COUNT(*) FROM $table");
        }

        return $counts;
    }

    public function test_running_twice_does_not_create_duplicates(): void
    {
        $this->seeder()->run(withTestAccounts: true, reset: true);
        $after1 = $this->counts();

        $this->seeder()->run(withTestAccounts: true);

        self::assertSame($after1, $this->counts());
    }

    public function test_exactly_one_current_conference(): void
    {
        $this->seeder()->run(reset: true);

        self::assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM conferences WHERE is_current = 1'));
        self::assertSame(Seeder::SAMPLE_YEAR, (int) $this->db->value('SELECT year FROM conferences WHERE is_current = 1'));
    }

    public function test_test_flag_creates_admin_and_editor_accounts(): void
    {
        $this->seeder()->run(withTestAccounts: true, reset: true);

        $roles = $this->db->query("SELECT email, role FROM users WHERE email IN ('admin@test.local', 'editor@test.local') ORDER BY email");
        self::assertSame([
            ['email' => 'admin@test.local', 'role' => 'admin'],
            ['email' => 'editor@test.local', 'role' => 'editor'],
        ], $roles);
        $hash = $this->db->value("SELECT password_hash FROM users WHERE email = 'admin@test.local'");
        self::assertTrue(password_verify(Seeder::TEST_PASSWORD, $hash));
    }

    public function test_without_test_flag_no_test_accounts(): void
    {
        $this->seeder()->run(reset: true);

        self::assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM users WHERE email LIKE '%@test.local'"));
    }

    public function test_reset_removes_existing_data(): void
    {
        $this->createConference(['name' => 'Dữ liệu cũ']);

        $this->seeder()->run(reset: true);

        self::assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM conferences WHERE name = N'Dữ liệu cũ'"));
    }

    public function test_cli_refuses_reset_without_test_flag(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/seed.php') . ' --reset 2>&1', $output, $exitCode);

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('--reset chỉ dùng được cùng --test', implode("\n", $output));
    }
}
