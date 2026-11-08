<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use App\Core\Clock;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class PageTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;
    private int $pageId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 00:00:00');
        $logo = $this->createMedia(['stored_path' => 'uploads/test/logo.png']);
        $this->conferenceId = $this->createCurrentConference(['name' => 'ELT 2026', 'logo_media_id' => $logo]);
        $this->pageId = $this->createPage($this->conferenceId, [
            'slug' => 'call-for-papers',
            'title' => 'Call for Papers',
            'body_html' => '<p>We invite researchers to submit abstracts on language teaching.</p>',
        ]);
        $group = $this->createMenuItem($this->conferenceId, ['label' => 'Conference']);
        $this->createMenuItem($this->conferenceId, ['parent_id' => $group, 'label' => 'Call for Papers', 'page_id' => $this->pageId]);
        $dates = $this->createPage($this->conferenceId, ['slug' => 'important-dates', 'title' => 'Important Dates']);
        $this->createMenuItem($this->conferenceId, ['parent_id' => $group, 'label' => 'Important Dates', 'page_id' => $dates, 'sort_order' => 20]);
    }

    public function test_public_page_has_title_meta_and_content(): void
    {
        $this->get('/call-for-papers');

        $this->assertStatus(200);
        $this->assertSee('<title>Call for Papers | ELT 2026</title>');
        $this->assertSee('<meta name="description" content="We invite researchers to submit abstracts on language teaching.">');
        $this->assertSee('<meta property="og:image" content="http://localhost:8000/uploads/test/logo.png">');
        $this->assertSee('<h1>Call for Papers</h1>');
        $this->assertSee('We invite researchers');
    }

    public function test_seo_fields_override_defaults(): void
    {
        $image = $this->createMedia(['stored_path' => 'uploads/test/share.jpg']);
        $this->db->update('pages', ['seo_title' => 'CFP 2026', 'seo_description' => 'Submit now.', 'featured_media_id' => $image], 'id = ?', [$this->pageId]);

        $this->get('/call-for-papers');

        $this->assertSee('<title>CFP 2026 | ELT 2026</title>');
        $this->assertSee('content="Submit now."');
        $this->assertSee('content="http://localhost:8000/uploads/test/share.jpg"');
    }

    public function test_has_related_pages_and_share_buttons(): void
    {
        $this->get('/call-for-papers');

        $this->assertSee('Related pages');
        $this->assertSee('href="/important-dates"');
        $this->assertSee('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode('http://localhost:8000/call-for-papers'));
        $this->assertSee('https://twitter.com/intent/tweet?url=' . rawurlencode('http://localhost:8000/call-for-papers'));
        $this->assertSee('https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode('http://localhost:8000/call-for-papers'));
        $this->assertSee('data-copy-link');
    }

    public function test_menu_shows_group_with_children(): void
    {
        $this->get('/call-for-papers');

        $this->assertSee('Conference');
        $this->assertSee('aria-current="page"');
    }

    public function test_draft_hidden_scheduled_and_missing_pages_return_404(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'draft-page', 'status' => 'draft', 'published_at' => null]);
        $this->createPage($this->conferenceId, ['slug' => 'hidden-page', 'status' => 'hidden']);
        $this->createPage($this->conferenceId, ['slug' => 'scheduled-page', 'published_at' => '2026-03-11 00:00:00']);

        foreach (['/draft-page', '/hidden-page', '/scheduled-page', '/no-such-page'] as $path) {
            $this->get($path);
            $this->assertStatus(404);
            $this->assertSee('Page not found');
        }
    }

    public function test_old_slug_redirects_permanently(): void
    {
        $this->db->insert('slug_redirects', ['conference_id' => $this->conferenceId, 'old_slug' => 'cfp', 'page_id' => $this->pageId]);

        $this->get('/cfp');

        $this->assertStatus(301);
        $this->assertRedirect('/call-for-papers');
    }

    public function test_hidden_page_menu_item_is_not_shown(): void
    {
        $this->db->update('pages', ['status' => 'hidden'], 'slug = ? AND conference_id = ?', ['important-dates', $this->conferenceId]);

        $this->get('/call-for-papers');

        $this->assertDontSee('href="/important-dates"');
    }
}
