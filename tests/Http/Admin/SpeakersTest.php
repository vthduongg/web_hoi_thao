<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class SpeakersTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->createPage($this->conferenceId, ['slug' => 'keynote-speakers', 'module' => 'keynote_speakers']);
        $this->loginAs('editor');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'photo_media_id' => '',
            'academic_title' => 'Prof. Dr.',
            'full_name' => 'Jane Doe',
            'affiliation' => 'University of Melbourne',
            'country' => 'Australia',
            'talk_title' => 'Teaching English in the AI era',
            'bio_html' => '<p>Jane is a professor.</p><script>alert(1)</script>',
        ], $overrides);
    }

    public function test_full_name_is_required(): void
    {
        $this->post('/admin/speakers/new', $this->form(['full_name' => '']));

        $this->assertStatus(422);
        $this->assertSee('Trường này là bắt buộc.');
    }

    public function test_public_cards_show_all_fields_in_order_with_sanitized_bio(): void
    {
        $photo = $this->createMedia(['stored_path' => 'uploads/t/jane.jpg']);
        $this->post('/admin/speakers/new', $this->form(['photo_media_id' => (string) $photo]));
        $this->assertRedirect('/admin/speakers');
        $this->post('/admin/speakers/new', $this->form(['full_name' => 'John Roe', 'academic_title' => 'Dr.']));

        $body = $this->get('/keynote-speakers')->body;

        foreach (['Prof. Dr. Jane Doe', 'University of Melbourne', 'Australia', 'Teaching English in the AI era', 'Jane is a professor.', 'src="/uploads/t/jane.jpg"'] as $text) {
            $this->assertSee($text);
        }
        $this->assertDontSee('alert(1)');
        self::assertLessThan(strpos($body, 'John Roe'), strpos($body, 'Jane Doe'));
    }

    public function test_hidden_speaker_is_not_shown(): void
    {
        $this->post('/admin/speakers/new', $this->form());
        $id = (int) $this->db->value("SELECT id FROM keynote_speakers WHERE full_name = 'Jane Doe' AND conference_id = ?", [$this->conferenceId]);

        $this->post("/admin/speakers/$id/toggle-visibility");

        $this->get('/keynote-speakers');
        $this->assertDontSee('Jane Doe');
        $this->assertSee('Information will be updated soon.');
    }
}
