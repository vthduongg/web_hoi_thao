<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class SitemapTest extends HttpTestCase
{
    use CreatesContent;

    public function test_sitemap_lists_only_public_pages_of_current_conference(): void
    {
        $current = $this->createCurrentConference(['year' => 6027]);
        $this->createPage($current, ['slug' => 'call-for-papers', 'status' => 'published']);
        $this->createPage($current, ['slug' => 'draft-page', 'status' => 'draft']);
        $this->createPage($current, ['slug' => 'deleted-page', 'deleted_at' => '2026-01-01']);
        $other = $this->createConference(['year' => 6020, 'has_been_current' => 1]);
        $this->createPage($other, ['slug' => 'old-only-page']);

        $response = $this->get('/sitemap.xml');

        $this->assertStatus(200);
        self::assertStringStartsWith('application/xml', $response->headers['Content-Type'] ?? '');
        $this->assertSee('<?xml version="1.0" encoding="UTF-8"?>');
        $this->assertSee('/call-for-papers</loc>');
        $this->assertSee('/</loc>');
        $this->assertDontSee('draft-page');
        $this->assertDontSee('deleted-page');
        $this->assertDontSee('old-only-page');
        self::assertNotFalse(simplexml_load_string($response->body), 'XML hợp lệ');
    }

    public function test_robots_allows_public_blocks_admin_and_points_to_sitemap(): void
    {
        $this->createCurrentConference();

        $this->get('/robots.txt');

        $this->assertStatus(200);
        $this->assertSee('Disallow: /admin');
        $this->assertSee('Sitemap: ');
        $this->assertSee('/sitemap.xml');
    }

    public function test_sitemap_without_current_conference_is_404(): void
    {
        $this->db->execute('UPDATE conferences SET is_current = 0');

        $this->get('/sitemap.xml');

        $this->assertStatus(404);
    }
}
