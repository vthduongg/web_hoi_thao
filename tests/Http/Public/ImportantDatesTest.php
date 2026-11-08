<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use App\Core\Clock;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class ImportantDatesTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 00:00:00');
        $this->conferenceId = $this->createCurrentConference();
        $this->createPage($this->conferenceId, ['slug' => 'important-dates', 'title' => 'Important Dates', 'module' => 'important_dates']);
        $this->createDate($this->conferenceId, ['label' => 'First announcement', 'date_start' => '2026-01-15', 'sort_order' => 10]);
        $this->createDate($this->conferenceId, ['label' => 'Abstract deadline', 'status' => 'extended', 'original_date' => '2026-03-01', 'date_start' => '2026-03-15', 'sort_order' => 20]);
        $this->createDate($this->conferenceId, ['label' => 'Full paper deadline', 'date_start' => '2026-05-30', 'sort_order' => 30]);
        $this->createDate($this->conferenceId, ['label' => 'Registration deadline', 'status' => 'tba', 'date_start' => null, 'sort_order' => 40]);
        $this->createDate($this->conferenceId, ['label' => 'Secret milestone', 'date_start' => '2026-03-12', 'is_visible' => 0, 'sort_order' => 50]);
    }

    public function test_dates_are_formatted_in_english(): void
    {
        $this->get('/important-dates');

        $this->assertStatus(200);
        $this->assertSee('15 January 2026');
        $this->assertSee('30 May 2026');
    }

    public function test_extended_date_shows_old_date_struck_and_label(): void
    {
        $this->get('/important-dates');

        $this->assertSee('<del>1 March 2026</del>');
        $this->assertSee('15 March 2026');
        $this->assertSee('Extended');
    }

    public function test_tba_date_shows_to_be_announced(): void
    {
        $this->get('/important-dates');

        $this->assertSee('To be announced');
    }

    public function test_exactly_one_upcoming_row_is_highlighted(): void
    {
        $body = $this->get('/important-dates')->body;

        self::assertSame(1, substr_count($body, 'dates-table__row--upcoming'));
        self::assertMatchesRegularExpression('/dates-table__row--upcoming[^>]*>\s*<td>Abstract deadline/', $body);
        $this->assertSee('Upcoming');
    }

    public function test_hidden_dates_are_not_listed(): void
    {
        $this->get('/important-dates');

        $this->assertDontSee('Secret milestone');
    }

    public function test_no_highlight_when_all_dates_passed(): void
    {
        Clock::freeze('2027-01-01 00:00:00');

        $body = $this->get('/important-dates')->body;

        self::assertSame(0, substr_count($body, 'dates-table__row--upcoming'));
    }

    public function test_empty_list_shows_update_message(): void
    {
        $this->db->execute('UPDATE important_dates SET is_visible = 0 WHERE conference_id = ?', [$this->conferenceId]);

        $this->get('/important-dates');

        $this->assertSee('Information will be updated soon.');
    }
}
