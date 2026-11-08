<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class SponsorsTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->createPage($this->conferenceId, ['slug' => 'sponsor', 'module' => 'sponsors']);
        $this->loginAs('editor');
    }

    private function tierId(string $name): int
    {
        return (int) $this->db->value('SELECT id FROM sponsor_tiers WHERE conference_id = ? AND name = ?', [$this->conferenceId, $name]);
    }

    public function test_name_and_logo_are_required(): void
    {
        $this->post('/admin/sponsor-tiers/new', ['name' => 'Organizer']);

        $this->post('/admin/sponsors/new', ['sponsor_tier_id' => (string) $this->tierId('Organizer'), 'name' => '', 'logo_media_id' => '', 'website_url' => '']);

        $this->assertStatus(422);
        self::assertSame(2, substr_count($this->lastResponse->body, 'Trường này là bắt buộc.'));
    }

    public function test_grouped_by_tier_in_order_with_alt_and_new_tab_link(): void
    {
        $this->post('/admin/sponsor-tiers/new', ['name' => 'Organizer']);
        $this->post('/admin/sponsor-tiers/new', ['name' => 'Sponsor']);
        $logo = $this->createMedia(['stored_path' => 'uploads/t/logo.png']);
        $this->post('/admin/sponsors/new', ['sponsor_tier_id' => (string) $this->tierId('Sponsor'), 'name' => 'ACME Books', 'logo_media_id' => (string) $logo, 'website_url' => 'https://acme.example.com']);
        $this->post('/admin/sponsors/new', ['sponsor_tier_id' => (string) $this->tierId('Organizer'), 'name' => 'HPU2', 'logo_media_id' => (string) $logo, 'website_url' => '']);
        $this->assertRedirect('/admin/sponsors');

        $body = $this->get('/sponsor')->body;

        self::assertLessThan(strpos($body, '>Sponsor<'), strpos($body, '>Organizer<'));
        $this->assertSee('alt="ACME Books"');
        $this->assertSee('href="https://acme.example.com" target="_blank" rel="noopener"');
    }

    public function test_hidden_sponsor_disappears(): void
    {
        $this->post('/admin/sponsor-tiers/new', ['name' => 'Sponsor']);
        $logo = $this->createMedia();
        $this->post('/admin/sponsors/new', ['sponsor_tier_id' => (string) $this->tierId('Sponsor'), 'name' => 'Gone Corp', 'logo_media_id' => (string) $logo, 'website_url' => '']);
        $id = (int) $this->db->value("SELECT id FROM sponsors WHERE name = 'Gone Corp'");

        $this->post("/admin/sponsors/$id/toggle-visibility");

        $this->get('/sponsor');
        $this->assertDontSee('Gone Corp');
    }

    public function test_website_must_be_http_url(): void
    {
        $this->post('/admin/sponsor-tiers/new', ['name' => 'Sponsor']);

        $this->post('/admin/sponsors/new', ['sponsor_tier_id' => (string) $this->tierId('Sponsor'), 'name' => 'X', 'logo_media_id' => (string) $this->createMedia(), 'website_url' => 'javascript:alert(1)']);

        $this->assertStatus(422);
    }
}
