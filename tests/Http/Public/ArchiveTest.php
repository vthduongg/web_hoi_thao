<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class ArchiveTest extends HttpTestCase
{
    use CreatesContent;

    private int $current;
    private int $archived;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->execute('UPDATE conferences SET is_current = 0');
        $this->current = $this->createConference(['name' => 'ELT 4027', 'year' => 4027, 'is_current' => 1, 'has_been_current' => 1]);
        $this->archived = $this->createConference(['name' => 'ELT 4025', 'year' => 4025, 'is_current' => 0, 'has_been_current' => 1]);
        $this->createConference(['name' => 'ELT 4024', 'year' => 4024, 'is_current' => 0, 'has_been_current' => 1]);
        $this->createConference(['name' => 'ELT 4028 draft', 'year' => 4028, 'is_current' => 0, 'has_been_current' => 0]);
        $this->createPage($this->current, ['slug' => 'call-for-papers', 'title' => 'CFP 4027']);
        $old = $this->createPage($this->archived, ['slug' => 'call-for-papers', 'title' => 'CFP 4025']);
        $this->createPage($this->archived, ['slug' => 'important-dates', 'title' => 'Dates 4025']);
        $group = $this->createMenuItem($this->archived, ['label' => 'Conference']);
        $this->createMenuItem($this->archived, ['parent_id' => $group, 'label' => 'Call for Papers', 'page_id' => $old]);
    }

    public function test_archived_home_and_page_show_banner_with_link_to_current(): void
    {
        $this->get('/4025');
        $this->assertStatus(200);
        $this->assertSee('This is an archived conference');
        $this->assertSee('ELT 4025');
        $this->assertSee('href="/"');

        $this->get('/4025/call-for-papers');
        $this->assertStatus(200);
        $this->assertSee('CFP 4025');
        $this->assertSee('This is an archived conference');
    }

    public function test_internal_links_on_archived_pages_keep_the_year_prefix(): void
    {
        $this->get('/4025/call-for-papers');

        $this->assertSee('href="/4025/call-for-papers"'); // menu
        $this->assertSee('href="/4025"'); // logo về trang chủ lưu trữ
        $this->assertDontSee('CFP 4027');
    }

    public function test_current_year_redirects_to_main_address(): void
    {
        $this->get('/4027');
        $this->assertStatus(301);
        $this->assertRedirect('/');

        $this->get('/4027/call-for-papers');
        $this->assertRedirect('/call-for-papers');
    }

    public function test_never_current_or_unknown_year_is_404(): void
    {
        foreach (['/4028', '/4028/call-for-papers', '/1999', '/4025/no-such-page'] as $path) {
            $this->get($path);
            $this->assertStatus(404);
        }
    }

    public function test_past_conferences_lists_archived_years_newest_first(): void
    {
        $body = $this->get('/past-conferences')->body;

        $this->assertStatus(200);
        $this->assertSee('href="/4025"');
        $this->assertSee('href="/4024"');
        $this->assertDontSee('ELT 4028 draft');
        $this->assertDontSee('href="/4027"');
        self::assertLessThan(strpos($body, 'ELT 4024'), strpos($body, 'ELT 4025'));
    }

    public function test_footer_links_to_past_conferences(): void
    {
        $this->get('/call-for-papers');

        $this->assertSee('href="/past-conferences"');
    }
}
