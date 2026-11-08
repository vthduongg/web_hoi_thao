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
    private const TABLES = ['conferences', 'users', 'important_dates', 'pages', 'menu_items', 'slides', 'media_files'];

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

    // ----- Dữ liệu mẫu US1 -----

    private function currentConferenceId(): int
    {
        return (int) $this->db->value('SELECT id FROM conferences WHERE is_current = 1');
    }

    public function test_seeds_eight_important_dates_with_special_roles(): void
    {
        $this->seeder()->run(reset: true);
        $id = $this->currentConferenceId();

        self::assertSame(8, (int) $this->db->value('SELECT COUNT(*) FROM important_dates WHERE conference_id = ?', [$id]));
        $roles = array_column($this->db->query('SELECT special_role FROM important_dates WHERE conference_id = ? AND special_role IS NOT NULL ORDER BY special_role', [$id]), 'special_role');
        self::assertSame(['abstract_deadline', 'conference_day', 'full_paper_deadline'], $roles);
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM important_dates WHERE conference_id = ? AND status = 'tba'", [$id]));
    }

    public function test_seeds_default_pages_from_fr003(): void
    {
        $this->seeder()->run(reset: true);

        $slugs = array_column($this->db->query('SELECT slug FROM pages WHERE conference_id = ? ORDER BY slug', [$this->currentConferenceId()]), 'slug');
        self::assertSame([
            'call-for-papers', 'contact', 'important-dates', 'keynote-speakers', 'organizing-committee',
            'paper-submission', 'program', 'scientific-committee', 'sponsor', 'travel-info',
        ], $slugs);
        self::assertSame('important_dates', $this->db->value("SELECT module FROM pages WHERE slug = 'important-dates'"));
    }

    public function test_seeds_menu_with_conference_group(): void
    {
        $this->seeder()->run(reset: true);
        $id = $this->currentConferenceId();

        $groupId = $this->db->value("SELECT id FROM menu_items WHERE conference_id = ? AND label = 'Conference' AND parent_id IS NULL", [$id]);
        $children = array_column($this->db->query('SELECT label FROM menu_items WHERE parent_id = ? ORDER BY sort_order', [$groupId]), 'label');
        self::assertSame(['Call for Papers', 'Important Dates', 'Scientific Committee', 'Organizing Committee', 'Keynote Speakers'], $children);
        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM menu_items WHERE conference_id = ? AND parent_id IS NULL AND page_id IS NULL AND label <> ?', [$id, 'Conference']));
    }

    public function test_slides_are_seeded_only_for_tests(): void
    {
        $this->seeder()->run(reset: true);
        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM slides'));

        $this->seeder()->run(withTestAccounts: true, reset: true);
        self::assertSame(3, (int) $this->db->value('SELECT COUNT(*) FROM slides'));
        self::assertFileExists(dirname(__DIR__, 3) . '/public/uploads/seed/slide-1.jpg');
    }

    public function test_does_not_steal_current_flag_from_existing_conference(): void
    {
        // Xoá hội thảo mẫu (nếu có) cùng dữ liệu con, để seed phải tạo hội thảo mẫu mới
        $this->seeder()->run(reset: true);
        $sampleId = $this->currentConferenceId();
        foreach (['slides', 'menu_items', 'pages', 'important_dates'] as $table) {
            $this->db->execute("DELETE FROM $table WHERE conference_id = ?", [$sampleId]);
        }
        $this->db->execute('DELETE FROM conferences WHERE id = ?', [$sampleId]);
        $existing = $this->createConference(['is_current' => 1]);

        $this->seeder()->run();

        self::assertSame($existing, $this->currentConferenceId());
    }

    public function test_cli_refuses_reset_without_test_flag(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 3) . '/bin/seed.php') . ' --reset 2>&1', $output, $exitCode);

        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('--reset chỉ dùng được cùng --test', implode("\n", $output));
    }
}
