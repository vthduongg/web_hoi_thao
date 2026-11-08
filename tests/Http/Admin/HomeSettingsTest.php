<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class HomeSettingsTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->loginAs('editor');
    }

    public function test_editor_edits_intro_and_it_is_sanitized(): void
    {
        $this->get('/admin/home');
        $this->assertStatus(200);

        $this->post('/admin/home/intro', ['home_intro_html' => '<p>Welcome to ELT!</p><script>alert(1)</script>']);

        $this->assertRedirect('/admin/home');
        self::assertSame('<p>Welcome to ELT!</p>', $this->db->value('SELECT home_intro_html FROM conferences WHERE id = ?', [$this->conferenceId]));
        $this->get('/');
        $this->assertSee('Welcome to ELT!');
    }

    public function test_slide_requires_image_and_alt_text(): void
    {
        $this->post('/admin/home/slides/new', ['media_id' => (string) $this->createMedia(), 'title' => 'Hall', 'alt_text' => '', 'link_url' => '']);

        $this->assertStatus(422);
        $this->assertSee('Trường này là bắt buộc.');
    }

    public function test_hidden_slide_disappears_and_slides_can_be_reordered(): void
    {
        $media = $this->createMedia(['stored_path' => 'uploads/t/hall.jpg']);
        $this->post('/admin/home/slides/new', ['media_id' => (string) $media, 'title' => 'Slide A', 'alt_text' => 'Hall A', 'link_url' => '']);
        $this->post('/admin/home/slides/new', ['media_id' => (string) $media, 'title' => 'Slide B', 'alt_text' => 'Hall B', 'link_url' => '']);
        $this->assertRedirect('/admin/home/slides');
        $a = (int) $this->db->value("SELECT id FROM slides WHERE title = 'Slide A' AND conference_id = ?", [$this->conferenceId]);
        $b = (int) $this->db->value("SELECT id FROM slides WHERE title = 'Slide B' AND conference_id = ?", [$this->conferenceId]);

        $this->post('/admin/home/slides/reorder', ['ids' => [(string) $b, (string) $a]]);
        $body = $this->get('/')->body;
        self::assertLessThan(strpos($body, 'Slide A'), strpos($body, 'Slide B'));

        $this->post("/admin/home/slides/$b/toggle-visibility");
        $this->get('/');
        $this->assertDontSee('Slide B');
    }

    public function test_highlights_are_managed_and_ordered(): void
    {
        $this->post('/admin/home/highlights/new', ['title' => 'Publication', 'body_text' => 'Papers will be published.', 'link_url' => '']);
        $this->post('/admin/home/highlights/new', ['title' => 'Awards', 'body_text' => 'Best paper awards.', 'link_url' => 'https://example.com/awards']);
        $first = (int) $this->db->value("SELECT id FROM highlights WHERE title = 'Publication'");
        $second = (int) $this->db->value("SELECT id FROM highlights WHERE title = 'Awards'");

        $this->post('/admin/home/highlights/reorder', ['ids' => [(string) $second, (string) $first]]);

        $body = $this->get('/')->body;
        self::assertLessThan(strpos($body, 'Publication'), strpos($body, 'Awards'));
        $this->assertSee('href="https://example.com/awards"');
    }
}
