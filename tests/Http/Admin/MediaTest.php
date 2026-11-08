<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

/** Màn hình Thư viện tệp (/admin/media). */
final class MediaTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference(['name' => 'Hội thảo test']);
        $this->loginAs('editor');
    }

    public function test_library_lists_searches_and_filters(): void
    {
        $this->createMedia(['original_name' => 'Ảnh hội trường lớn.jpg', 'stored_path' => 'uploads/t/hall.jpg']);
        $this->createMedia(['original_name' => 'Mẫu bài viết.docx', 'stored_path' => 'uploads/t/a.docx', 'kind' => 'document', 'mime_type' => 'application/msword']);

        $this->get('/admin/media');
        $this->assertStatus(200);
        $this->assertSee('Ảnh hội trường lớn.jpg');
        $this->assertSee('Mẫu bài viết.docx');
        $this->assertSee('Ảnh JPG, PNG, WebP, GIF tối đa 5 MB; tài liệu PDF, DOC, DOCX tối đa 20 MB.');

        $this->get('/admin/media?q=' . rawurlencode('hội trường'));
        $this->assertDontSee('Mẫu bài viết.docx');

        $this->get('/admin/media?kind=document');
        $this->assertSee('Mẫu bài viết.docx');
        $this->assertDontSee('Ảnh hội trường lớn.jpg');
    }

    public function test_upload_from_library_returns_to_library_with_type_error(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'm');
        file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        $this->post('/admin/media/upload', ['return' => 'library'], ['files' => ['name' => ['x.svg'], 'tmp_name' => [$tmp], 'size' => [10], 'error' => [UPLOAD_ERR_OK]]]);

        $this->assertRedirect('/admin/media');
        $this->get('/admin/media');
        $this->assertSee('Chỉ nhận JPG, PNG, WebP, GIF, PDF, DOC, DOCX.');
        @unlink($tmp);
    }

    public function test_edit_alt_text(): void
    {
        $id = $this->createMedia(['original_name' => 'hall.jpg']);

        $this->get("/admin/media/$id");
        $this->assertStatus(200);
        $this->post("/admin/media/$id", ['alt_text' => 'Hội trường chính']);

        $this->assertRedirect('/admin/media');
        self::assertSame('Hội trường chính', $this->db->value('SELECT alt_text FROM media_files WHERE id = ?', [$id]));
    }

    public function test_deleting_file_in_use_lists_usages_then_moves_to_trash_after_confirmation(): void
    {
        $id = $this->createMedia(['original_name' => 'temple.jpg', 'stored_path' => 'uploads/t/temple.jpg']);
        $this->createPage($this->conferenceId, ['title' => 'Travel Info', 'body_html' => '<img src="/uploads/t/temple.jpg" alt="Temple">']);

        $this->post("/admin/media/$id/delete");
        $this->assertStatus(200);
        $this->assertSee('Tệp này đang được dùng ở:');
        $this->assertSee('Nội dung trang — Travel Info');
        $this->assertSee('name="confirmed" value="1"');
        self::assertNull($this->db->value('SELECT deleted_at FROM media_files WHERE id = ?', [$id]));

        $this->post("/admin/media/$id/delete", ['confirmed' => '1']);
        $this->assertRedirect('/admin/media');
        self::assertNotNull($this->db->value('SELECT deleted_at FROM media_files WHERE id = ?', [$id]));
    }

    public function test_unused_file_is_moved_to_trash_directly(): void
    {
        $id = $this->createMedia(['original_name' => 'unused.jpg', 'stored_path' => 'uploads/t/unused.jpg']);

        $this->post("/admin/media/$id/delete");

        $this->assertRedirect('/admin/media');
        self::assertNotNull($this->db->value('SELECT deleted_at FROM media_files WHERE id = ?', [$id]));
    }
}
