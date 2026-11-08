<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class DatesTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-01-10 01:00:00');
        $this->conferenceId = $this->createCurrentConference();
        $this->createPage($this->conferenceId, ['slug' => 'important-dates', 'module' => 'important_dates']);
        $this->loginAs('editor');
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Abstract submission deadline',
            'status' => 'normal',
            'date_start' => '2026-03-15',
            'date_end' => '',
            'original_date' => '',
            'special_role' => '',
        ], $overrides);
    }

    private function dateId(string $label): int
    {
        return (int) $this->db->value('SELECT id FROM important_dates WHERE conference_id = ? AND label = ?', [$this->conferenceId, $label]);
    }

    public function test_create_list_and_edit(): void
    {
        $this->get('/admin/dates/new');
        $this->assertStatus(200);

        $this->post('/admin/dates/new', $this->form());
        $this->assertRedirect('/admin/dates');

        $id = $this->dateId('Abstract submission deadline');
        $this->get('/admin/dates');
        $this->assertSee('Abstract submission deadline');
        $this->assertSee('15/03/2026');

        $this->post("/admin/dates/$id", $this->form(['label' => 'Hạn nộp tóm tắt']));
        $this->assertRedirect('/admin/dates');
        self::assertSame('Hạn nộp tóm tắt', $this->db->value('SELECT label FROM important_dates WHERE id = ?', [$id]));
    }

    public function test_validation_rules(): void
    {
        $this->post('/admin/dates/new', $this->form(['status' => 'extended', 'original_date' => '']));
        $this->assertStatus(422);
        $this->assertSee('Mốc gia hạn cần có ngày cũ.');

        $this->post('/admin/dates/new', $this->form(['date_start' => '']));
        $this->assertStatus(422);
        $this->assertSee('Cần có ngày, trừ khi chọn &quot;Sẽ thông báo sau&quot;.');

        $this->post('/admin/dates/new', $this->form(['date_start' => '2026-06-20', 'date_end' => '2026-06-19']));
        $this->assertStatus(422);
        $this->assertSee('Ngày kết thúc không được trước ngày bắt đầu.');
    }

    public function test_tba_does_not_need_a_date(): void
    {
        $this->post('/admin/dates/new', $this->form(['label' => 'Registration', 'status' => 'tba', 'date_start' => '']));

        $this->assertRedirect('/admin/dates');
        self::assertNull($this->db->value('SELECT date_start FROM important_dates WHERE id = ?', [$this->dateId('Registration')]));
    }

    public function test_special_role_must_be_unique(): void
    {
        $this->createDate($this->conferenceId, ['label' => 'Old', 'special_role' => 'abstract_deadline']);

        $this->post('/admin/dates/new', $this->form(['special_role' => 'abstract_deadline']));

        $this->assertStatus(422);
        $this->assertSee('Đã có mốc khác giữ vai trò này.');
    }

    public function test_changing_abstract_deadline_updates_home_and_dates_page(): void
    {
        $this->post('/admin/dates/new', $this->form(['special_role' => 'abstract_deadline']));
        $id = $this->dateId('Abstract submission deadline');

        $this->post("/admin/dates/$id", $this->form(['special_role' => 'abstract_deadline', 'status' => 'extended', 'original_date' => '2026-03-15', 'date_start' => '2026-03-31']));

        foreach (['/', '/important-dates'] as $path) {
            $this->get($path);
            $this->assertSee('<del>15 March 2026</del>');
            $this->assertSee('31 March 2026');
        }
    }

    public function test_toggle_visibility_reorder_and_delete(): void
    {
        $a = $this->createDate($this->conferenceId, ['label' => 'Mốc A', 'sort_order' => 10]);
        $b = $this->createDate($this->conferenceId, ['label' => 'Mốc B', 'sort_order' => 20]);

        $this->post("/admin/dates/$a/toggle-visibility");
        $this->assertRedirect('/admin/dates');
        $this->get('/important-dates');
        $this->assertDontSee('Mốc A');

        $response = $this->post('/admin/dates/reorder', ['ids' => [(string) $b, (string) $a]]);
        $this->assertStatus(200);
        self::assertSame('application/json; charset=UTF-8', $response->headers['Content-Type']);
        self::assertSame([$b, $a], array_column($this->db->query('SELECT id FROM important_dates WHERE conference_id = ? ORDER BY sort_order', [$this->conferenceId]), 'id'));

        $this->post("/admin/dates/$b/delete");
        $this->assertRedirect('/admin/dates');
        self::assertNotNull($this->db->value('SELECT deleted_at FROM important_dates WHERE id = ?', [$b]));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'delete' AND entity_type = 'important_dates' AND entity_id = ?", [$b]));
    }

    public function test_reorder_rejects_ids_of_other_conference(): void
    {
        $mine = $this->createDate($this->conferenceId);
        $other = $this->createDate($this->createConference());

        $this->post('/admin/dates/reorder', ['ids' => [(string) $other, (string) $mine]]);

        $this->assertStatus(422);
    }

    public function test_dates_of_other_conference_are_not_editable(): void
    {
        $other = $this->createDate($this->createConference());

        $this->get("/admin/dates/$other");

        $this->assertStatus(404);
    }
}
