<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use App\Core\Clock;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class HomeTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-02-01 00:00:00');
        $this->conferenceId = $this->createCurrentConference([
            'name' => 'International Conference on ELT 2026',
            'theme' => 'Practices, Opportunities and Challenges',
            'venue' => 'Hanoi Pedagogical University 2',
            'conference_language' => 'English',
            'home_intro_html' => '<p>Welcome researchers.</p>',
        ]);
        $this->createDate($this->conferenceId, ['label' => 'Abstract submission deadline', 'date_start' => '2026-03-15', 'special_role' => 'abstract_deadline']);
        $this->createDate($this->conferenceId, ['label' => 'Full paper submission deadline', 'date_start' => '2026-05-30', 'special_role' => 'full_paper_deadline']);
        $this->createDate($this->conferenceId, ['label' => 'Conference', 'date_start' => '2026-06-20', 'date_end' => '2026-06-21', 'special_role' => 'conference_day']);
        $this->createPage($this->conferenceId, ['slug' => 'call-for-papers', 'title' => 'Call for Papers']);
        $this->createPage($this->conferenceId, ['slug' => 'paper-submission', 'title' => 'Paper Submission']);
    }

    public function test_shows_key_conference_information(): void
    {
        $this->get('/');

        $this->assertStatus(200);
        $this->assertSee('<html lang="en">');
        $this->assertSee('<title>International Conference on ELT 2026</title>');
        $this->assertSee('International Conference on ELT 2026');
        $this->assertSee('Practices, Opportunities and Challenges');
        $this->assertSee('Welcome researchers.');
        $this->assertSee('Hanoi Pedagogical University 2');
        $this->assertSee('English');
        $this->assertSee('20 June 2026 – 21 June 2026');
        $this->assertSee('15 March 2026');
        $this->assertSee('30 May 2026');
    }

    public function test_links_to_call_for_papers_and_paper_submission(): void
    {
        $this->get('/');

        $this->assertSee('href="/call-for-papers"');
        $this->assertSee('href="/paper-submission"');
    }

    public function test_extended_deadline_on_home_shows_old_date_struck_through(): void
    {
        $this->db->execute("UPDATE important_dates SET status = 'extended', original_date = '2026-03-01' WHERE special_role = 'abstract_deadline' AND conference_id = ?", [$this->conferenceId]);

        $this->get('/');

        $this->assertSee('<del>1 March 2026</del>');
        $this->assertSee('15 March 2026');
    }

    public function test_hidden_deadline_is_not_shown(): void
    {
        $this->db->execute("UPDATE important_dates SET is_visible = 0 WHERE special_role = 'full_paper_deadline'");

        $this->get('/');

        $this->assertDontSee('30 May 2026');
    }

    public function test_shows_visible_slides_and_highlights_in_order(): void
    {
        $media = $this->createMedia(['stored_path' => 'uploads/test/slide.jpg']);
        $this->db->insert('slides', ['conference_id' => $this->conferenceId, 'media_id' => $media, 'title' => 'Second slide', 'alt_text' => 'Hall B', 'sort_order' => 20, 'is_visible' => 1]);
        $this->db->insert('slides', ['conference_id' => $this->conferenceId, 'media_id' => $media, 'title' => 'First slide', 'alt_text' => 'Hall A', 'sort_order' => 10, 'is_visible' => 1]);
        $this->db->insert('slides', ['conference_id' => $this->conferenceId, 'media_id' => $media, 'title' => 'Off slide', 'alt_text' => 'Off', 'sort_order' => 5, 'is_visible' => 0]);
        $this->db->insert('highlights', ['conference_id' => $this->conferenceId, 'title' => 'Publication', 'body_text' => 'Selected papers will be published.', 'sort_order' => 10, 'is_visible' => 1]);

        $body = $this->get('/')->body;

        self::assertLessThan(strpos($body, 'Second slide'), strpos($body, 'First slide'));
        $this->assertSee('alt="Hall A"');
        $this->assertDontSee('Off slide');
        $this->assertSee('Selected papers will be published.');
    }

    public function test_returns_404_when_there_is_no_current_conference(): void
    {
        $this->db->execute('UPDATE conferences SET is_current = 0');

        $this->get('/');

        $this->assertStatus(404);
    }
}
