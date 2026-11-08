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
    public const SAMPLE_YEAR = 2027;
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
            $this->seedConferenceContent($conferenceId, $withTestAccounts);
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
            'name' => 'International Conference on English Language Teaching 2027',
            'theme' => 'Practices, Opportunities and Challenges',
            'year' => self::SAMPLE_YEAR,
            'venue' => 'Hanoi Pedagogical University 2, Xuan Hoa, Phuc Yen, Vietnam',
            'conference_language' => 'English',
            'public_language' => 'en',
            'contact_email' => 'conference@example.edu.vn',
            'contact_phone' => '+84 211 3863 203',
            'footer_text' => 'Faculty of Foreign Languages, Hanoi Pedagogical University 2',
            'copyright_text' => '© 2027 Hanoi Pedagogical University 2',
            'submission_url' => 'https://forms.example.com/conference-2027',
            'home_intro_html' => '<p>The conference brings together researchers, lecturers and teachers to share research and practice in English language teaching.</p>',
            // Chỉ làm hiện hành khi CSDL chưa có hội thảo hiện hành nào
            'is_current' => $this->db->value('SELECT COUNT(*) FROM conferences WHERE is_current = 1') > 0 ? 0 : 1,
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
    private function seedConferenceContent(int $conferenceId, bool $isTest): void
    {
        $this->seedImportantDates($conferenceId);
        $pageIds = $this->seedPages($conferenceId);
        $this->seedMenu($conferenceId, $pageIds);
        if ($isTest) {
            $this->seedSlides($conferenceId);
        }
    }

    /** Có dữ liệu của hội thảo trong bảng chưa (để chạy lại không tạo trùng). */
    private function hasRows(string $table, int $conferenceId): bool
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM $table WHERE conference_id = ?", [$conferenceId]) > 0;
    }

    // ----- US1: mốc thời gian -----

    private function seedImportantDates(int $conferenceId): void
    {
        if ($this->hasRows('important_dates', $conferenceId)) {
            return;
        }
        $dates = [
            ['First announcement', 'normal', '2026-10-15', null, null],
            ['Second announcement', 'normal', '2026-12-15', null, null],
            ['Abstract submission deadline', 'normal', '2027-01-31', null, 'abstract_deadline'],
            ['Notification of abstract acceptance', 'normal', '2027-02-28', null, null],
            ['Full paper submission deadline', 'normal', '2027-04-30', null, 'full_paper_deadline'],
            ['Notification of full paper acceptance', 'normal', '2027-05-20', null, null],
            ['Registration deadline', 'tba', null, null, null],
            ['Conference days', 'normal', '2027-06-18', '2027-06-19', 'conference_day'],
        ];
        foreach ($dates as $index => [$label, $status, $start, $end, $role]) {
            $this->db->insert('important_dates', [
                'conference_id' => $conferenceId,
                'label' => $label,
                'status' => $status,
                'date_start' => $start,
                'date_end' => $end,
                'special_role' => $role,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    // ----- US1: các trang mặc định (FR-003) -----

    /** @return array<string, int> slug => id trang */
    private function seedPages(int $conferenceId): array
    {
        if (!$this->hasRows('pages', $conferenceId)) {
            foreach (SamplePages::all() as $page) {
                $this->db->insert('pages', [
                    'conference_id' => $conferenceId,
                    'title' => $page['title'],
                    'slug' => $page['slug'],
                    'body_html' => $page['body'],
                    'module' => $page['module'],
                    'status' => 'published',
                    'published_at' => '2026-01-01 00:00:00',
                ]);
            }
        }

        $ids = [];
        foreach ($this->db->query('SELECT id, slug FROM pages WHERE conference_id = ? AND deleted_at IS NULL', [$conferenceId]) as $row) {
            $ids[$row['slug']] = (int) $row['id'];
        }

        return $ids;
    }

    // ----- US1: menu mặc định -----

    /** @param array<string, int> $pageIds */
    private function seedMenu(int $conferenceId, array $pageIds): void
    {
        if ($this->hasRows('menu_items', $conferenceId)) {
            return;
        }
        $add = fn (string $label, ?string $slug, int $sort, ?int $parent = null): int => $this->db->insert('menu_items', [
            'conference_id' => $conferenceId,
            'parent_id' => $parent,
            'label' => $label,
            'page_id' => $slug === null ? null : ($pageIds[$slug] ?? null),
            'sort_order' => $sort,
        ]);

        $conference = $add('Conference', null, 10);
        $add('Call for Papers', 'call-for-papers', 10, $conference);
        $add('Important Dates', 'important-dates', 20, $conference);
        $add('Scientific Committee', 'scientific-committee', 30, $conference);
        $add('Organizing Committee', 'organizing-committee', 40, $conference);
        $add('Keynote Speakers', 'keynote-speakers', 50, $conference);
        $add('Program', 'program', 20);
        $add('Paper Submission', 'paper-submission', 30);
        $add('Sponsor', 'sponsor', 40);
        $add('Contact', 'contact', 50);
        $add('Travel Info', 'travel-info', 60);
    }

    // ----- US1: ảnh slider (chỉ dữ liệu test, dùng ảnh mẫu trong tests/fixtures) -----

    private function seedSlides(int $conferenceId): void
    {
        if ($this->hasRows('slides', $conferenceId)) {
            return;
        }
        $targetDir = $this->rootDir . '/public/uploads/seed';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        foreach ([1, 2, 3] as $number) {
            $file = "slide-$number.jpg";
            copy($this->rootDir . "/tests/fixtures/$file", "$targetDir/$file");
            $mediaId = $this->db->insert('media_files', [
                'original_name' => $file,
                'stored_path' => "uploads/seed/$file",
                'mime_type' => 'image/jpeg',
                'size_bytes' => filesize("$targetDir/$file"),
                'kind' => 'image',
                'width' => 1200,
                'height' => 450,
                'alt_text' => "Sample slide $number",
            ]);
            $this->db->insert('slides', [
                'conference_id' => $conferenceId,
                'media_id' => $mediaId,
                'title' => "Sample slide $number",
                'alt_text' => "Sample slide $number",
                'sort_order' => $number * 10,
            ]);
        }
    }
}
