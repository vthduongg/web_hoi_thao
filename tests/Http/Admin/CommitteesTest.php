<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class CommitteesTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;
    private int $pageId;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 3) . '/lang'));
        $this->conferenceId = $this->createCurrentConference();
        $this->pageId = $this->createPage($this->conferenceId, ['slug' => 'organizing-committee', 'title' => 'Organizing Committee', 'module' => 'committees']);
        $this->loginAs('editor');
    }

    private function committeeId(string $name): int
    {
        return (int) $this->db->value('SELECT id FROM committees WHERE conference_id = ? AND name = ?', [$this->conferenceId, $name]);
    }

    private function addMember(int $committeeId, string $name, array $extra = []): void
    {
        $this->post("/admin/committees/$committeeId/members/new", array_merge([
            'academic_title' => 'Prof.', 'full_name' => $name, 'position_affiliation' => 'HPU2', 'committee_role' => 'Member', 'photo_media_id' => '',
        ], $extra));
    }

    private function csvUpload(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $content);

        return ['file' => ['name' => 'members.csv', 'tmp_name' => $path, 'size' => strlen($content), 'error' => UPLOAD_ERR_OK]];
    }

    public function test_two_committees_on_one_page_in_order_with_numbered_members(): void
    {
        $this->post('/admin/committees/new', ['name' => 'Steering Committee', 'page_ids' => [(string) $this->pageId]]);
        $this->assertRedirect('/admin/committees');
        $this->post('/admin/committees/new', ['name' => 'Organizing Committee Members', 'page_ids' => [(string) $this->pageId]]);
        $steering = $this->committeeId('Steering Committee');
        $this->addMember($steering, 'Nguyễn Văn An', ['committee_role' => 'Chair']);
        $this->addMember($steering, 'Hidden Person');
        $this->addMember($steering, 'Trần Thị Bình');
        $hidden = (int) $this->db->value("SELECT id FROM committee_members WHERE full_name = 'Hidden Person'");
        $this->post("/admin/committees/$steering/members/$hidden/toggle-visibility");

        $body = $this->get('/organizing-committee')->body;

        self::assertLessThan(strpos($body, 'Organizing Committee Members'), strpos($body, 'Steering Committee'));
        foreach (['No.', 'Name', 'Position &amp; Affiliation', 'Role', 'Prof. Nguyễn Văn An', 'Chair'] as $text) {
            $this->assertSee($text);
        }
        $this->assertDontSee('Hidden Person');
        self::assertMatchesRegularExpression('/<td>2<\/td>\s*<td>Prof\. Trần Thị Bình/', $body, 'STT đánh lại theo danh sách đang hiện');
    }

    public function test_empty_committee_shows_update_message(): void
    {
        $this->post('/admin/committees/new', ['name' => 'Scientific Committee', 'page_ids' => [(string) $this->pageId]]);

        $this->get('/organizing-committee');

        $this->assertSee('Scientific Committee');
        $this->assertSee('Information will be updated soon.');
    }

    public function test_member_full_name_is_required(): void
    {
        $this->post('/admin/committees/new', ['name' => 'Ban A']);
        $id = $this->committeeId('Ban A');

        $this->addMember($id, '');

        $this->assertStatus(422);
        $this->assertSee('Trường này là bắt buộc.');
    }

    public function test_import_appends_thirty_members_in_file_order(): void
    {
        $this->post('/admin/committees/new', ['name' => 'Ban nhập']);
        $id = $this->committeeId('Ban nhập');
        $this->addMember($id, 'Thành viên có sẵn');
        $csv = "Họ tên,Học hàm học vị,Vai trò\n";
        for ($i = 1; $i <= 30; $i++) {
            $csv .= "Người số $i,TS.,Uỷ viên\n";
        }

        $this->get("/admin/committees/$id/import");
        $this->assertStatus(200);
        $this->assertSee('/assets/templates/committee-import-template.xlsx');
        $this->post("/admin/committees/$id/import", [], $this->csvUpload($csv));

        $this->assertRedirect("/admin/committees/$id/members");
        $names = array_column($this->db->query('SELECT full_name FROM committee_members WHERE committee_id = ? ORDER BY sort_order', [$id]), 'full_name');
        self::assertCount(31, $names);
        self::assertSame('Thành viên có sẵn', $names[0]);
        self::assertSame('Người số 1', $names[1]);
        self::assertSame('Người số 30', $names[30]);
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'import' AND entity_id = ?", [$id]));
    }

    public function test_import_with_errors_adds_nothing(): void
    {
        $this->post('/admin/committees/new', ['name' => 'Ban lỗi']);
        $id = $this->committeeId('Ban lỗi');

        // Dòng 3 trống (bỏ qua), dòng 5 có vai trò nhưng thiếu họ tên
        $this->post("/admin/committees/$id/import", [], $this->csvUpload("Họ tên,Vai trò\nNgười 1,Uỷ viên\n\nNgười 3,Uỷ viên\n,Thư ký\n"));

        $this->assertStatus(422);
        $this->assertSee('Dòng 5: thiếu Họ tên');
        self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM committee_members WHERE committee_id = ?', [$id]));
    }

    public function test_members_of_committee_in_other_conference_are_not_accessible(): void
    {
        $other = $this->db->insert('committees', ['conference_id' => $this->createConference(), 'name' => 'Khác', 'sort_order' => 10]);

        $this->get("/admin/committees/$other/members");

        $this->assertStatus(404);
    }
}
