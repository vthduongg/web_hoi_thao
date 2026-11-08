<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class TravelContactTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference(['contact_email' => 'elt@hpu2.edu.vn', 'contact_phone' => '+84 211 3863 203']);
    }

    public function test_pasted_map_and_video_links_become_responsive_iframes(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'travel-info', 'title' => 'Travel Info', 'status' => 'draft', 'published_at' => null]);
        $id = (int) $this->db->value("SELECT id FROM pages WHERE slug = 'travel-info' AND conference_id = ?", [$this->conferenceId]);
        $this->loginAs('editor');

        $this->post("/admin/pages/$id", [
            'title' => 'Travel Info', 'slug' => 'travel-info', 'module' => 'none', 'seo_title' => '', 'seo_description' => '',
            'featured_media_id' => '', 'tags' => '', 'status' => 'published', 'published_at' => '', 'version' => '1',
            'body_html' => '<p>https://www.google.com/maps/embed?pb=!1m18</p><p>https://www.youtube.com/watch?v=abc123</p>'
                . '<div class="attraction-card"><img src="/uploads/t/temple.jpg" alt="Tay Thien Temple"><h3>Tay Thien</h3><p>A famous pagoda.</p></div>',
        ]);

        $body = $this->get('/travel-info')->body;

        $this->assertStatus(200);
        self::assertSame(2, substr_count($body, '<div class="embed-responsive"><iframe'));
        $this->assertSee('src="https://www.google.com/maps/embed?pb=!1m18"');
        $this->assertSee('src="https://www.youtube-nocookie.com/embed/abc123"');
        $this->assertSee('<div class="attraction-card"><img loading="lazy" src="/uploads/t/temple.jpg" alt="Tay Thien Temple"');
        $this->assertSee('A famous pagoda.');
    }

    public function test_contact_page_has_mailto_and_tel_links_in_footer(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'contact', 'module' => 'contacts']);

        $this->get('/contact');

        $this->assertSee('href="mailto:elt@hpu2.edu.vn"');
        $this->assertSee('href="tel:+842113863203"');
    }

    public function test_every_public_page_has_menu_button_with_aria_expanded(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'contact', 'module' => 'contacts']);

        foreach (['/', '/contact'] as $path) {
            $this->get($path);
            $this->assertSee('data-menu-toggle aria-expanded="false" aria-controls="main-menu"');
        }
    }
}
