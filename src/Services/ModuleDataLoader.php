<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Conference;
use App\Models\Page;
use App\Repositories\CommitteeRepository;
use App\Repositories\ContactRepository;
use App\Repositories\ImportantDateRepository;
use App\Repositories\MediaRepository;
use App\Repositories\ProgramRepository;
use App\Repositories\SpeakerRepository;
use App\Repositories\SponsorRepository;

/**
 * Nạp dữ liệu cho khối hiển thị dưới nội dung trang (cột pages.module) — contracts/public-routes.md.
 * Mỗi khối có một template trong templates/public/modules/{module}.php.
 */
final class ModuleDataLoader
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<string, mixed> biến truyền cho template của khối; rỗng nếu trang không có khối */
    public function load(Page $page, Conference $conference): array
    {
        return match ($page->module) {
            'important_dates' => $this->importantDates($conference),
            'committees' => ['committees' => (new CommitteeRepository($this->db))->visibleForPage($page->id)],
            'keynote_speakers' => ['speakers' => (new SpeakerRepository($this->db))->visibleForConference($conference->id)],
            'program' => $this->program($conference),
            'sponsors' => ['tiers' => (new SponsorRepository($this->db))->visibleTiers($conference->id)],
            'contacts' => ['contacts' => (new ContactRepository($this->db))->visibleForConference($conference->id)],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function importantDates(Conference $conference): array
    {
        $dates = (new ImportantDateRepository($this->db))->visibleForConference($conference->id);

        return ['dates' => $dates, 'upcoming' => UpcomingDateFinder::find($dates)];
    }

    /** Chương trình chi tiết; nếu chưa có mục nào thì dùng tệp ảnh/PDF thay thế (FR-007). */
    private function program(Conference $conference): array
    {
        return [
            'days' => (new ProgramRepository($this->db))->publicProgram($conference->id),
            'programFile' => (new MediaRepository($this->db))->find($conference->programFileMediaId),
        ];
    }
}
