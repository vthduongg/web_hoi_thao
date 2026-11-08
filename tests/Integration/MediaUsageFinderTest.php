<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\MediaUsageFinder;
use Tests\Support\CreatesContent;
use Tests\Support\DatabaseTestCase;

final class MediaUsageFinderTest extends DatabaseTestCase
{
    use CreatesContent;

    public function test_finds_every_kind_of_usage(): void
    {
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $media = $this->createMedia(['stored_path' => 'uploads/2026/03/abc.jpg']);
        $conferenceId = $this->createConference(['name' => 'Hội thảo A', 'logo_media_id' => $media, 'favicon_media_id' => $media, 'program_file_media_id' => $media, 'home_intro_html' => '<img src="/uploads/2026/03/abc.jpg" alt="x">']);
        $this->db->insert('slides', ['conference_id' => $conferenceId, 'media_id' => $media, 'title' => 'Slide hội trường', 'alt_text' => 'Hall', 'sort_order' => 10]);
        $committee = $this->db->insert('committees', ['conference_id' => $conferenceId, 'name' => 'Ban', 'sort_order' => 10]);
        $this->db->insert('committee_members', ['committee_id' => $committee, 'full_name' => 'Thành viên X', 'photo_media_id' => $media, 'sort_order' => 10]);
        $this->db->insert('keynote_speakers', ['conference_id' => $conferenceId, 'full_name' => 'Diễn giả Y', 'photo_media_id' => $media, 'sort_order' => 10]);
        $this->db->insert('keynote_speakers', ['conference_id' => $conferenceId, 'full_name' => 'Diễn giả Z', 'bio_html' => '<p><img src="/uploads/2026/03/abc.jpg" alt="z"></p>', 'sort_order' => 20]);
        $tier = $this->db->insert('sponsor_tiers', ['conference_id' => $conferenceId, 'name' => 'Sponsor', 'sort_order' => 10]);
        $this->db->insert('sponsors', ['conference_id' => $conferenceId, 'sponsor_tier_id' => $tier, 'name' => 'Nhà tài trợ Q', 'logo_media_id' => $media, 'sort_order' => 10]);
        $this->createPage($conferenceId, ['title' => 'Trang có ảnh đại diện', 'featured_media_id' => $media]);
        $this->createPage($conferenceId, ['title' => 'Travel Info', 'body_html' => '<p><img src="/uploads/2026/03/abc.jpg" alt="t"></p>']);

        $usages = (new MediaUsageFinder($this->db))->find($media);

        foreach ([
            'Logo — Hội thảo A', 'Favicon — Hội thảo A', 'Tệp chương trình — Hội thảo A', 'Đoạn giới thiệu trang chủ — Hội thảo A',
            'Ảnh slider — Slide hội trường', 'Ảnh thành viên ban — Thành viên X', 'Ảnh diễn giả — Diễn giả Y',
            'Tiểu sử diễn giả — Diễn giả Z', 'Logo nhà tài trợ — Nhà tài trợ Q',
            'Ảnh chia sẻ của trang — Trang có ảnh đại diện', 'Nội dung trang — Travel Info',
        ] as $expected) {
            self::assertContains($expected, $usages);
        }
        self::assertCount(11, $usages);
    }

    public function test_unused_file_has_no_usages_and_deleted_rows_are_ignored(): void
    {
        $media = $this->createMedia(['stored_path' => 'uploads/2026/03/unused.jpg']);
        $this->createPage($this->createConference(), ['body_html' => '<img src="/uploads/2026/03/unused.jpg">', 'deleted_at' => '2026-01-01']);

        self::assertSame([], (new MediaUsageFinder($this->db))->find($media));
    }

    public function test_similar_file_names_do_not_match(): void
    {
        $media = $this->createMedia(['stored_path' => 'uploads/2026/03/abc.jpg']);
        $this->createPage($this->createConference(), ['body_html' => '<img src="/uploads/2026/03/abc.jpg.old">']);

        self::assertSame([], (new MediaUsageFinder($this->db))->find($media));
    }
}
