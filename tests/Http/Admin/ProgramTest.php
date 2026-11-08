<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class ProgramTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->createPage($this->conferenceId, ['slug' => 'program', 'module' => 'program']);
        $this->loginAs('editor');
    }

    private function createDaySessionItem(array $itemOverrides = []): array
    {
        $this->post('/admin/program/days/new', ['day_date' => '2027-06-18', 'title' => 'Day 1']);
        $dayId = (int) $this->db->value('SELECT id FROM program_days WHERE conference_id = ?', [$this->conferenceId]);
        $this->post("/admin/program/days/$dayId/sessions/new", ['title' => 'Plenary session', 'room' => 'Hall A']);
        $sessionId = (int) $this->db->value('SELECT id FROM program_sessions WHERE program_day_id = ?', [$dayId]);
        $this->post("/admin/program/sessions/$sessionId/items/new", array_merge([
            'start_time' => '08:30', 'end_time' => '09:15', 'content' => 'Opening speech', 'presenter' => 'Rector', 'room' => '',
        ], $itemOverrides));

        return [$dayId, $sessionId];
    }

    public function test_day_session_item_shown_on_public_page_with_session_room(): void
    {
        $this->createDaySessionItem();

        $this->get('/admin/program');
        $this->assertStatus(200);
        $this->assertSee('Opening speech');

        $this->get('/program');
        foreach (['18 June 2027', 'Day 1', 'Plenary session', '08:30 – 09:15', 'Opening speech', 'Rector', 'Hall A'] as $text) {
            $this->assertSee($text);
        }
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        [, $sessionId] = $this->createDaySessionItem();

        $this->post("/admin/program/sessions/$sessionId/items/new", ['start_time' => '10:00', 'end_time' => '10:00', 'content' => 'Bad', 'presenter' => '', 'room' => '']);

        $this->assertStatus(422);
        $this->assertSee('Giờ kết thúc phải sau giờ bắt đầu.');
    }

    public function test_program_file_is_shown_when_no_items(): void
    {
        $pdf = $this->createMedia(['original_name' => 'program.pdf', 'stored_path' => 'uploads/t/program.pdf', 'kind' => 'document', 'mime_type' => 'application/pdf']);

        $this->post('/admin/program/file', ['program_file_media_id' => (string) $pdf]);

        $this->assertRedirect('/admin/program');
        $this->get('/program');
        $this->assertSee('href="/uploads/t/program.pdf"');
    }

    public function test_program_image_is_shown_as_image(): void
    {
        $img = $this->createMedia(['stored_path' => 'uploads/t/program.jpg']);
        $this->post('/admin/program/file', ['program_file_media_id' => (string) $img]);

        $this->get('/program');

        $this->assertSee('<img src="/uploads/t/program.jpg"');
    }

    public function test_nothing_shows_update_message(): void
    {
        $this->get('/program');

        $this->assertSee('Information will be updated soon.');
    }

    public function test_sessions_of_other_conference_are_not_accessible(): void
    {
        $otherDay = $this->db->insert('program_days', ['conference_id' => $this->createConference(), 'day_date' => '2027-01-01', 'sort_order' => 10]);

        $this->get("/admin/program/days/$otherDay/sessions/new");

        $this->assertStatus(404);
    }
}
