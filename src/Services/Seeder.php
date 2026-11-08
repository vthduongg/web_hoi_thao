<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Nạp dữ liệu mẫu để chạy thử website (dùng bởi bin/seed.php).
 *
 * Chạy lại nhiều lần không tạo trùng: mỗi phần kiểm tra dữ liệu đã có trước khi thêm.
 * Mỗi user story bổ sung dữ liệu mẫu của mình bằng một hàm seedXxx() riêng.
 */
final class Seeder
{
    public const SAMPLE_YEAR = 2026;
    public const TEST_PASSWORD = 'Test@12345';

    /**
     * Thứ tự xoá bảng khi --reset (bảng con trước, bảng cha sau).
     * Bảng chưa tồn tại được bỏ qua.
     */
    private const TABLES_IN_DELETE_ORDER = [
        'program_items', 'program_sessions', 'program_days',
        'sponsors', 'sponsor_tiers', 'contact_persons', 'keynote_speakers',
        'page_committees', 'committee_members', 'committees',
        'slides', 'highlights', 'menu_items', 'slug_redirects', 'page_tags', 'page_revisions', 'pages',
        'important_dates', 'activity_logs', 'password_reset_tokens', 'users',
        'conferences', 'media_files',
    ];

    public function __construct(private readonly Database $db, private readonly string $rootDir)
    {
    }

    public function run(bool $withTestAccounts = false, bool $reset = false): void
    {
        $this->db->transaction(function () use ($withTestAccounts, $reset): void {
            if ($reset) {
                $this->deleteAllData();
            }
            $conferenceId = $this->seedConference();
            if ($withTestAccounts) {
                $this->seedTestAccounts();
            }
            $this->seedConferenceContent($conferenceId);
        });
    }

    private function deleteAllData(): void
    {
        foreach (self::TABLES_IN_DELETE_ORDER as $table) {
            if ($this->db->value('SELECT OBJECT_ID(?, ?)', [$table, 'U']) !== null) {
                $this->db->execute("DELETE FROM $table");
            }
        }
    }

    private function seedConference(): int
    {
        $existing = $this->db->value('SELECT id FROM conferences WHERE year = ?', [self::SAMPLE_YEAR]);
        if ($existing !== null) {
            return (int) $existing;
        }

        return $this->db->insert('conferences', [
            'name' => 'International Conference on English Language Teaching 2026',
            'theme' => 'Practices, Opportunities and Challenges',
            'year' => self::SAMPLE_YEAR,
            'venue' => 'Hanoi Pedagogical University 2, Xuan Hoa, Phuc Yen, Vietnam',
            'conference_language' => 'English',
            'public_language' => 'en',
            'contact_email' => 'conference@example.edu.vn',
            'contact_phone' => '+84 211 3863 203',
            'footer_text' => 'Faculty of Foreign Languages, Hanoi Pedagogical University 2',
            'copyright_text' => '© 2026 Hanoi Pedagogical University 2',
            'submission_url' => 'https://forms.example.com/conference-2026',
            'home_intro_html' => '<p>The conference brings together researchers, lecturers and teachers to share research and practice in English language teaching.</p>',
            'is_current' => 1,
            'has_been_current' => 1,
        ]);
    }

    private function seedTestAccounts(): void
    {
        foreach (['admin@test.local' => 'admin', 'editor@test.local' => 'editor'] as $email => $role) {
            if ($this->db->value('SELECT id FROM users WHERE email = ?', [$email]) !== null) {
                continue;
            }
            $this->db->insert('users', [
                'email' => $email,
                'full_name' => $role === 'admin' ? 'Quản trị viên Test' : 'Biên tập viên Test',
                'password_hash' => password_hash(self::TEST_PASSWORD, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
        }
    }

    /** Dữ liệu mẫu của các user story; mỗi story thêm một dòng gọi hàm của mình. */
    private function seedConferenceContent(int $conferenceId): void
    {
    }
}
