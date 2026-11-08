<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class MenuTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;
    private int $pageId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->pageId = $this->createPage($this->conferenceId, ['slug' => 'program', 'title' => 'Program']);
    }

    private function menuId(string $label): int
    {
        return (int) $this->db->value('SELECT id FROM menu_items WHERE conference_id = ? AND label = ?', [$this->conferenceId, $label]);
    }

    public function test_only_admin_can_manage_menu(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/menu');
        $this->assertStatus(403);
        $this->post('/admin/menu/new', ['label' => 'X', 'page_id' => '', 'external_url' => '']);
        $this->assertStatus(403);
    }

    public function test_admin_adds_items_and_saves_tree(): void
    {
        $this->loginAs('admin');
        $this->post('/admin/menu/new', ['label' => 'Conference', 'page_id' => '', 'external_url' => '']);
        $this->post('/admin/menu/new', ['label' => 'Program menu', 'page_id' => (string) $this->pageId, 'external_url' => '']);
        $this->assertRedirect('/admin/menu');
        $group = $this->menuId('Conference');
        $program = $this->menuId('Program menu');

        $response = $this->post('/admin/menu/reorder', ['items' => [
            ['id' => (string) $program, 'parent_id' => ''],
            ['id' => (string) $group, 'parent_id' => ''],
        ]]);
        $this->assertStatus(200);
        self::assertSame('application/json; charset=UTF-8', $response->headers['Content-Type']);
        self::assertSame([$program, $group], array_column($this->db->query('SELECT id FROM menu_items WHERE conference_id = ? AND parent_id IS NULL ORDER BY sort_order', [$this->conferenceId]), 'id'));

        $this->post('/admin/menu/reorder', ['items' => [
            ['id' => (string) $group, 'parent_id' => ''],
            ['id' => (string) $program, 'parent_id' => (string) $group],
        ]]);
        self::assertSame($group, (int) $this->db->value('SELECT parent_id FROM menu_items WHERE id = ?', [$program]));

        $this->get('/program');
        $this->assertSee('data-submenu-toggle');
        $this->assertSee('href="/program"');
    }

    public function test_third_level_is_rejected(): void
    {
        $this->loginAs('admin');
        $a = $this->createMenuItem($this->conferenceId, ['label' => 'A']);
        $b = $this->createMenuItem($this->conferenceId, ['label' => 'B']);
        $c = $this->createMenuItem($this->conferenceId, ['label' => 'C']);

        $response = $this->post('/admin/menu/reorder', ['items' => [
            ['id' => (string) $a, 'parent_id' => ''],
            ['id' => (string) $b, 'parent_id' => (string) $a],
            ['id' => (string) $c, 'parent_id' => (string) $b],
        ]]);

        $this->assertStatus(422);
        self::assertSame('Menu tối đa 2 cấp.', json_decode($response->body, true)['error']);
        self::assertNull($this->db->value('SELECT parent_id FROM menu_items WHERE id = ?', [$c]), 'Không lưu gì khi lỗi');
    }

    public function test_reorder_rejects_items_of_other_conference(): void
    {
        $this->loginAs('admin');
        $mine = $this->createMenuItem($this->conferenceId);
        $other = $this->createMenuItem($this->createConference());

        $this->post('/admin/menu/reorder', ['items' => [['id' => (string) $mine, 'parent_id' => ''], ['id' => (string) $other, 'parent_id' => '']]]);

        $this->assertStatus(422);
    }

    public function test_item_cannot_have_both_page_and_external_link(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/menu/new', ['label' => 'Both', 'page_id' => (string) $this->pageId, 'external_url' => 'https://example.com']);

        $this->assertStatus(422);
        $this->assertSee('Chọn trang trong website HOẶC nhập link ngoài, không chọn cả hai.');
    }

    public function test_external_link_opens_in_new_tab_on_public_menu(): void
    {
        $this->loginAs('admin');
        $this->post('/admin/menu/new', ['label' => 'Registration form', 'page_id' => '', 'external_url' => 'https://forms.example.com/register']);

        $this->get('/program');

        $this->assertSee('href="https://forms.example.com/register"');
        self::assertMatchesRegularExpression('#href="https://forms\.example\.com/register"\s+target="_blank" rel="noopener"#', $this->lastResponse->body);
    }
}
