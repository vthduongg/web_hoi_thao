<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ActivityLogger;
use App\Services\ConferenceCopier;
use Tests\Support\CreatesContent;
use Tests\Support\DatabaseTestCase;

final class ConferenceCopierTest extends DatabaseTestCase
{
    use CreatesContent;

    private int $source;
    private int $logo;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $this->logo = $this->createMedia();
        $program = $this->createMedia(['stored_path' => 'uploads/t/program.pdf', 'kind' => 'document', 'mime_type' => 'application/pdf']);
        $this->source = $this->createConference([
            'name' => 'ELT Conference 3025', 'year' => 3025, 'copyright_text' => '© 3025 HPU2', 'is_current' => 0, 'has_been_current' => 1,
            'logo_media_id' => $this->logo, 'program_file_media_id' => $program,
        ]);
    }

    private function copier(): ConferenceCopier
    {
        return new ConferenceCopier($this->db, new ActivityLogger($this->db));
    }

    private function countRows(string $sql, int $conferenceId): int
    {
        return (int) $this->db->value($sql, [$conferenceId]);
    }

    private function seedSourceContent(): array
    {
        $cfp = $this->createPage($this->source, ['slug' => 'call-for-papers', 'title' => 'CFP', 'status' => 'published']);
        $committeePage = $this->createPage($this->source, ['slug' => 'committee', 'module' => 'committees']);
        $this->createPage($this->source, ['slug' => 'old-deleted', 'deleted_at' => '2026-01-01']);
        $this->tagPage($cfp, 'deadline');
        $this->db->insert('page_revisions', ['page_id' => $cfp, 'revision_no' => 1, 'title' => 'CFP', 'module' => 'none']);
        $this->db->insert('slug_redirects', ['conference_id' => $this->source, 'old_slug' => 'cfp-old', 'page_id' => $cfp]);
        $group = $this->createMenuItem($this->source, ['label' => 'Conference']);
        $this->createMenuItem($this->source, ['label' => 'CFP', 'parent_id' => $group, 'page_id' => $cfp]);
        $this->db->insert('slides', ['conference_id' => $this->source, 'media_id' => $this->logo, 'alt_text' => 'Hall', 'sort_order' => 10]);
        $this->db->insert('highlights', ['conference_id' => $this->source, 'title' => 'Awards', 'sort_order' => 10]);
        $this->createDate($this->source, ['label' => 'Abstract deadline', 'special_role' => 'abstract_deadline']);
        $committee = $this->db->insert('committees', ['conference_id' => $this->source, 'name' => 'Steering', 'sort_order' => 10]);
        $this->db->insert('committee_members', ['committee_id' => $committee, 'full_name' => 'Member A', 'sort_order' => 10]);
        $this->db->insertRow('page_committees', ['page_id' => $committeePage, 'committee_id' => $committee]);
        $tier = $this->db->insert('sponsor_tiers', ['conference_id' => $this->source, 'name' => 'Organizer', 'sort_order' => 10]);
        $this->db->insert('sponsors', ['conference_id' => $this->source, 'sponsor_tier_id' => $tier, 'name' => 'HPU2', 'logo_media_id' => $this->logo, 'sort_order' => 10]);
        $this->db->insert('contact_persons', ['conference_id' => $this->source, 'full_name' => 'Contact A', 'sort_order' => 10]);
        $this->db->insert('keynote_speakers', ['conference_id' => $this->source, 'full_name' => 'Speaker A', 'sort_order' => 10]);
        $day = $this->db->insert('program_days', ['conference_id' => $this->source, 'day_date' => '3025-06-01', 'sort_order' => 10]);

        return ['cfp' => $cfp, 'committeePage' => $committeePage];
    }

    public function test_copies_general_information_as_new_unpublished_conference(): void
    {
        $newId = $this->copier()->copy($this->source, 3026, null);

        $copy = $this->db->first('SELECT * FROM conferences WHERE id = ?', [$newId]);
        self::assertSame(3026, $copy['year']);
        self::assertSame('ELT Conference 3026', $copy['name']);
        self::assertSame('© 3026 HPU2', $copy['copyright_text']);
        self::assertSame(0, (int) $copy['is_current']);
        self::assertSame(0, (int) $copy['has_been_current']);
        self::assertSame($this->logo, $copy['logo_media_id'], 'Tệp dùng chung, không nhân bản');
        self::assertNull($copy['program_file_media_id']);
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'copy_conference' AND entity_id = ?", [$newId]));
    }

    public function test_copies_content_according_to_copy_table(): void
    {
        $this->seedSourceContent();

        $newId = $this->copier()->copy($this->source, 3026, null);

        self::assertSame(['call-for-papers', 'committee'], array_column($this->db->query('SELECT slug FROM pages WHERE conference_id = ? ORDER BY slug', [$newId]), 'slug'));
        self::assertSame(0, $this->countRows("SELECT COUNT(*) FROM pages WHERE conference_id = ? AND status <> 'draft'", $newId), 'Trang mới ở trạng thái nháp');
        self::assertSame(1, $this->countRows('SELECT COUNT(*) FROM page_tags t JOIN pages p ON p.id = t.page_id WHERE p.conference_id = ?', $newId));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM page_revisions r JOIN pages p ON p.id = r.page_id WHERE p.conference_id = ?', $newId));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM slug_redirects WHERE conference_id = ?', $newId));
        foreach (['slides', 'highlights', 'important_dates', 'committees', 'sponsor_tiers', 'sponsors', 'contact_persons'] as $table) {
            self::assertSame(1, $this->countRows("SELECT COUNT(*) FROM $table WHERE conference_id = ?", $newId), $table);
        }
        self::assertSame(1, $this->countRows('SELECT COUNT(*) FROM committee_members m JOIN committees c ON c.id = m.committee_id WHERE c.conference_id = ?', $newId));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM keynote_speakers WHERE conference_id = ?', $newId));
        self::assertSame(0, $this->countRows('SELECT COUNT(*) FROM program_days WHERE conference_id = ?', $newId));
    }

    public function test_menu_and_committee_links_point_to_the_new_copies(): void
    {
        $this->seedSourceContent();

        $newId = $this->copier()->copy($this->source, 3026, null);

        $child = $this->db->first("SELECT m.page_id, m.parent_id FROM menu_items m WHERE m.conference_id = ? AND m.label = 'CFP'", [$newId]);
        self::assertSame((int) $this->db->value("SELECT id FROM pages WHERE conference_id = ? AND slug = 'call-for-papers'", [$newId]), $child['page_id']);
        self::assertSame((int) $this->db->value("SELECT id FROM menu_items WHERE conference_id = ? AND label = 'Conference'", [$newId]), $child['parent_id']);
        $link = $this->db->first('SELECT pc.page_id, pc.committee_id FROM page_committees pc JOIN committees c ON c.id = pc.committee_id WHERE c.conference_id = ?', [$newId]);
        self::assertSame((int) $this->db->value("SELECT id FROM pages WHERE conference_id = ? AND slug = 'committee'", [$newId]), $link['page_id']);
    }

    public function test_editing_the_copy_does_not_change_the_source(): void
    {
        ['cfp' => $cfp] = $this->seedSourceContent();
        $newId = $this->copier()->copy($this->source, 3026, null);

        $this->db->execute("UPDATE pages SET title = N'Bản 3026' WHERE conference_id = ? AND slug = 'call-for-papers'", [$newId]);

        self::assertSame('CFP', $this->db->value('SELECT title FROM pages WHERE id = ?', [$cfp]));
    }

    public function test_duplicate_or_invalid_year_is_rejected(): void
    {
        $this->createConference(['year' => 3027]);

        foreach ([3027, 3025, 99] as $year) {
            try {
                $this->copier()->copy($this->source, $year, null);
                self::fail("Phải từ chối năm $year");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_failure_midway_rolls_back_everything(): void
    {
        $this->seedSourceContent();
        $failing = new class ($this->db, new ActivityLogger($this->db)) extends ConferenceCopier {
            protected function copyContacts(int $fromId, int $toId): void
            {
                throw new \RuntimeException('lỗi giả lập ở bước cuối');
            }
        };

        try {
            $failing->copy($this->source, 3026, null);
            self::fail('Phải ném lỗi');
        } catch (\RuntimeException) {
        }

        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM conferences WHERE year = 3026'));
        self::assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM pages p JOIN conferences c ON c.id = p.conference_id WHERE c.year = 3026"));
    }
}
