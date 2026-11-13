<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Env;

/**
 * Nạp nội dung thật của hội thảo VietESL 2025 (dùng bởi bin/seed-vietesl2025.php).
 *
 * Ảnh được đưa vào thư viện qua MediaUploader nên có đủ bản thu nhỏ như ảnh tải lên từ trang quản trị.
 * Không chạy nếu CSDL đã có hội thảo năm 2025 (để không tạo trùng).
 */
final class VietEsl2025Seeder
{
    /** @var array<string, int> tên tệp ảnh => id trong thư viện */
    private array $media = [];

    /** @param ?string $publicDir nơi lưu ảnh tải lên (mặc định $rootDir/public; test truyền thư mục tạm) */
    public function __construct(private readonly Database $db, private readonly string $rootDir, private readonly ?string $publicDir = null)
    {
    }

    /** @return int id hội thảo vừa tạo */
    public function run(): int
    {
        if ($this->db->value('SELECT id FROM conferences WHERE year = ?', [VietEsl2025Data::YEAR]) !== null) {
            throw new \RuntimeException('CSDL đã có hội thảo năm ' . VietEsl2025Data::YEAR . '.');
        }

        return $this->db->transaction(function (): int {
            $this->uploadImages();
            $conferenceId = $this->insertConference();
            $this->insertImportantDates($conferenceId);
            $pageIds = $this->insertPages($conferenceId);
            $this->insertMenu($conferenceId, $pageIds);
            $this->insertCommittees($conferenceId, $pageIds);
            $this->insertSpeakers($conferenceId);
            $this->insertProgram($conferenceId);
            $this->insertSponsors($conferenceId);
            $this->insertSimpleRows($conferenceId);

            return $conferenceId;
        });
    }

    private function uploadImages(): void
    {
        $uploader = new MediaUploader($this->db, $this->publicDir ?? $this->rootDir . '/public');
        $alts = [
            'hpu2-logo.png' => 'Hanoi Pedagogical University 2 logo',
            'nflp-logo.png' => 'National Foreign Languages Project logo',
            'relo-us-embassy.jpg' => 'Regional English Language Office, U.S. Embassy Hanoi logo',
            'agenda.png' => 'VietESL 2025 conference agenda, 14 November 2025',
        ];
        foreach (VietEsl2025Data::SPEAKERS as [$file, $title, $name]) {
            $alts[$file] = "$title $name";
        }
        foreach ($alts as $file => $alt) {
            $path = $this->rootDir . '/' . VietEsl2025Data::IMAGE_DIR . '/' . $file;
            $uploaded = $uploader->upload(['name' => $file, 'tmp_name' => $path, 'size' => (int) filesize($path), 'error' => UPLOAD_ERR_OK], null, $alt);
            $this->media[$file] = $uploaded->id;
        }
    }

    private function insertConference(): int
    {
        $hasCurrent = (int) $this->db->value('SELECT COUNT(*) FROM conferences WHERE is_current = 1') > 0;

        return $this->db->insert('conferences', VietEsl2025Data::CONFERENCE + [
            'logo_media_id' => $this->media['hpu2-logo.png'],
            'favicon_media_id' => $this->media['hpu2-logo.png'],
            'program_file_media_id' => $this->media['agenda.png'],
            'is_current' => $hasCurrent ? 0 : 1,
            'has_been_current' => 1,
        ]);
    }

    private function insertImportantDates(int $conferenceId): void
    {
        foreach (VietEsl2025Data::IMPORTANT_DATES as $index => [$label, $status, $date, $originalDate, $role]) {
            $this->db->insert('important_dates', [
                'conference_id' => $conferenceId, 'label' => $label, 'status' => $status, 'date_start' => $date,
                'original_date' => $originalDate, 'special_role' => $role, 'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    /** @return array<string, int> slug => id trang */
    private function insertPages(int $conferenceId): array
    {
        $ids = [];
        foreach (VietEsl2025Content::pages() as $page) {
            $ids[$page['slug']] = $this->db->insert('pages', [
                'conference_id' => $conferenceId, 'title' => $page['title'], 'slug' => $page['slug'],
                'body_html' => $page['body'], 'module' => $page['module'],
                'status' => 'published', 'published_at' => '2025-07-25 00:00:00',
            ]);
        }

        return $ids;
    }

    /** @param array<string, int> $pageIds */
    private function insertMenu(int $conferenceId, array $pageIds): void
    {
        $add = fn (string $label, ?string $slug, int $sort, ?int $parent): int => $this->db->insert('menu_items', [
            'conference_id' => $conferenceId, 'parent_id' => $parent, 'label' => $label,
            'page_id' => $slug === null ? null : $pageIds[$slug], 'sort_order' => $sort,
        ]);
        foreach (VietEsl2025Data::MENU as $index => [$label, $slug, $children]) {
            $parentId = $add($label, $slug, ($index + 1) * 10, null);
            foreach ($children as $childIndex => [$childLabel, $childSlug]) {
                $add($childLabel, $childSlug, ($childIndex + 1) * 10, $parentId);
            }
        }
    }

    /** @param array<string, int> $pageIds */
    private function insertCommittees(int $conferenceId, array $pageIds): void
    {
        foreach (VietEsl2025Data::COMMITTEES as $index => [$name, $pageSlug, $members]) {
            $committeeId = $this->db->insert('committees', ['conference_id' => $conferenceId, 'name' => $name, 'sort_order' => ($index + 1) * 10]);
            $this->db->insertRow('page_committees', ['page_id' => $pageIds[$pageSlug], 'committee_id' => $committeeId, 'sort_order' => ($index + 1) * 10]);
            foreach ($members as $position => [$title, $fullName, $affiliation, $role]) {
                $this->db->insert('committee_members', [
                    'committee_id' => $committeeId, 'academic_title' => $title, 'full_name' => $fullName,
                    'position_affiliation' => $affiliation, 'committee_role' => $role, 'sort_order' => ($position + 1) * 10,
                ]);
            }
        }
    }

    private function insertSpeakers(int $conferenceId): void
    {
        foreach (VietEsl2025Data::SPEAKERS as $index => [$photo, $title, $name, $affiliation, $country, $talk]) {
            $this->db->insert('keynote_speakers', [
                'conference_id' => $conferenceId, 'photo_media_id' => $this->media[$photo], 'academic_title' => $title,
                'full_name' => $name, 'affiliation' => $affiliation, 'country' => $country, 'talk_title' => $talk,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    private function insertProgram(int $conferenceId): void
    {
        $dayId = $this->db->insert('program_days', ['conference_id' => $conferenceId, 'day_date' => '2025-11-14', 'title' => 'Conference agenda', 'sort_order' => 10]);
        foreach (VietEsl2025Content::programSessions() as $index => [$title, $room, $items]) {
            $sessionId = $this->db->insert('program_sessions', ['program_day_id' => $dayId, 'title' => $title, 'room' => $room, 'sort_order' => ($index + 1) * 10]);
            foreach ($items as $position => [$start, $end, $content, $presenter, $itemRoom]) {
                $this->db->insert('program_items', [
                    'program_session_id' => $sessionId, 'start_time' => "$start:00", 'end_time' => "$end:00",
                    'content' => $content, 'presenter' => $presenter, 'room' => $itemRoom, 'sort_order' => ($position + 1) * 10,
                ]);
            }
        }
    }

    private function insertSponsors(int $conferenceId): void
    {
        $tiers = [];
        foreach (VietEsl2025Data::SPONSORS as $index => [$tier, $name, $logo, $website]) {
            $tiers[$tier] ??= $this->db->insert('sponsor_tiers', ['conference_id' => $conferenceId, 'name' => $tier, 'sort_order' => (count($tiers) + 1) * 10]);
            $this->db->insert('sponsors', [
                'conference_id' => $conferenceId, 'sponsor_tier_id' => $tiers[$tier], 'name' => $name,
                'logo_media_id' => $this->media[$logo], 'website_url' => $website, 'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    /** Người liên hệ và khối nổi bật trên trang chủ. */
    private function insertSimpleRows(int $conferenceId): void
    {
        foreach (VietEsl2025Data::CONTACTS as $index => [$name, $affiliation, $email, $phone]) {
            $this->db->insert('contact_persons', [
                'conference_id' => $conferenceId, 'full_name' => $name, 'affiliation' => $affiliation,
                'email' => $email, 'phone' => $phone, 'sort_order' => ($index + 1) * 10,
            ]);
        }
        foreach (VietEsl2025Data::HIGHLIGHTS as $index => [$title, $body, $link]) {
            $this->db->insert('highlights', [
                'conference_id' => $conferenceId, 'title' => $title, 'body_text' => $body, 'link_url' => rtrim(Env::get('APP_URL', 'http://localhost:8000'), '/') . $link, 'sort_order' => ($index + 1) * 10,
            ]);
        }
    }
}
