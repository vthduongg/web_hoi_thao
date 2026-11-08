<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class PageDeleteTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference();
        $this->loginAs('editor');
    }

    public function test_page_in_menu_asks_for_second_confirmation(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'program', 'title' => 'Program']);
        $this->createMenuItem($this->conferenceId, ['label' => 'Chương trình hội thảo', 'page_id' => $id]);

        $this->post("/admin/pages/$id/delete");

        $this->assertStatus(200);
        $this->assertSee('Trang này đang được menu sử dụng');
        $this->assertSee('Chương trình hội thảo');
        $this->assertSee('name="confirmed" value="1"');
        self::assertNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$id]));
    }

    public function test_confirmed_delete_moves_to_trash_and_hides_menu_item(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'program', 'title' => 'Program']);
        $this->createMenuItem($this->conferenceId, ['label' => 'Program menu', 'page_id' => $id]);
        $this->createPage($this->conferenceId, ['slug' => 'contact']);

        $this->post("/admin/pages/$id/delete", ['confirmed' => '1']);

        $this->assertRedirect('/admin/pages');
        self::assertNotNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$id]));
        $this->get('/program');
        $this->assertStatus(404);
        $this->get('/contact');
        $this->assertDontSee('Program menu');
    }

    public function test_page_not_in_menu_is_deleted_directly(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'old-news']);

        $this->post("/admin/pages/$id/delete");

        $this->assertRedirect('/admin/pages');
        self::assertNotNull($this->db->value('SELECT deleted_at FROM pages WHERE id = ?', [$id]));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'delete' AND entity_type = 'pages' AND entity_id = ?", [$id]));
    }

    public function test_list_has_delete_button_with_confirmation(): void
    {
        $this->createPage($this->conferenceId, ['title' => 'Trang cần xoá']);

        $this->get('/admin/pages');

        $this->assertSee('data-confirm=');
    }
}
