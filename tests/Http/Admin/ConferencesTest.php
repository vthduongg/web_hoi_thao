<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class ConferencesTest extends HttpTestCase
{
    use CreatesContent;

    private int $current;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->execute('UPDATE conferences SET is_current = 0');
        $this->current = $this->createConference(['name' => 'ELT 5027', 'year' => 5027, 'is_current' => 1, 'has_been_current' => 1]);
        $this->createPage($this->current, ['slug' => 'call-for-papers', 'title' => 'CFP 5027']);
    }

    public function test_only_admin_can_list_copy_and_make_current(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/conferences');
        $this->assertStatus(403);
        $this->post("/admin/conferences/{$this->current}/copy", ['year' => '5028']);
        $this->assertStatus(403);
        $this->post("/admin/conferences/{$this->current}/make-current");
        $this->assertStatus(403);
    }

    public function test_admin_copies_and_makes_new_conference_current(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/conferences');
        $this->assertStatus(200);
        $this->assertSee('ELT 5027');

        $this->post("/admin/conferences/{$this->current}/copy", ['year' => '5028']);
        $this->assertRedirect('/admin/conferences');
        $newId = (int) $this->db->value('SELECT id FROM conferences WHERE year = 5028');
        self::assertSame('ELT 5028', $this->db->value('SELECT name FROM conferences WHERE id = ?', [$newId]));

        $this->post("/admin/conferences/$newId/make-current");
        $this->assertRedirect('/admin/conferences');
        self::assertSame($newId, (int) $this->db->value('SELECT id FROM conferences WHERE is_current = 1'));
        self::assertSame(1, (int) $this->db->value('SELECT has_been_current FROM conferences WHERE id = ?', [$newId]));

        $this->get('/5027');
        $this->assertStatus(200);
        $this->assertSee('This is an archived conference');
    }

    public function test_copy_with_taken_year_shows_error(): void
    {
        $this->createConference(['year' => 5029]);
        $this->loginAs('admin');

        $this->post("/admin/conferences/{$this->current}/copy", ['year' => '5029']);

        $this->assertStatus(422);
        $this->assertSee('Đã có hội thảo khác dùng năm này.');
    }

    public function test_editor_switches_editing_conference_and_admin_data_follows(): void
    {
        $other = $this->createConference(['name' => 'ELT 5026', 'year' => 5026, 'has_been_current' => 1]);
        $this->createPage($other, ['slug' => 'call-for-papers', 'title' => 'CFP 5026']);
        $this->loginAs('editor');

        $this->get('/admin/pages');
        $this->assertSee('CFP 5027');
        $this->assertSee('id="switch-conference"');

        $this->post('/admin/switch-conference', ['conference_id' => (string) $other]);
        $this->assertRedirect('/admin');

        $this->get('/admin/pages');
        $this->assertSee('CFP 5026');
        $this->assertDontSee('CFP 5027');
    }

    public function test_switch_to_unknown_conference_is_404(): void
    {
        $this->loginAs('editor');

        $this->post('/admin/switch-conference', ['conference_id' => '999999']);

        $this->assertStatus(404);
    }
}
