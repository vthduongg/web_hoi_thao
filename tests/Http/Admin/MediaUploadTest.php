<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\HttpTestCase;

/** POST /admin/media/upload — tải tệp lên thư viện từ hộp chọn tệp. */
final class MediaUploadTest extends HttpTestCase
{
    /** @var list<string> tệp đã tạo trong public/ để xoá khi xong */
    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->db->query('SELECT stored_path, thumb_small_path, thumb_large_path FROM media_files WHERE original_name LIKE ?', ['upload-test-%']) as $row) {
            foreach ($row as $path) {
                if ($path !== null && is_file(dirname(__DIR__, 3) . '/public/' . $path)) {
                    unlink(dirname(__DIR__, 3) . '/public/' . $path);
                }
            }
        }
        foreach ($this->createdFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function fileField(string $name, string $content): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, $content);
        $this->createdFiles[] = $tmp;

        return ['name' => [$name], 'tmp_name' => [$tmp], 'size' => [strlen($content)], 'error' => [UPLOAD_ERR_OK], 'type' => ['application/octet-stream']];
    }

    public function test_uploads_valid_image_and_returns_to_picker(): void
    {
        $this->loginAs('editor');

        $this->post('/admin/media/upload', [], ['files' => $this->fileField('upload-test-photo.jpg', (string) file_get_contents(dirname(__DIR__, 2) . '/fixtures/slide-1.jpg'))]);

        $this->assertRedirect('/admin/media/picker');
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM media_files WHERE original_name = 'upload-test-photo.jpg'"));
        $this->get('/admin/media/picker');
        $this->assertSee('Đã tải lên 1 tệp.');
    }

    public function test_rejects_svg_with_message_listing_allowed_types(): void
    {
        $this->loginAs('editor');

        $this->post('/admin/media/upload', ['kind' => 'image'], ['files' => $this->fileField('upload-test-logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>')]);

        $this->assertRedirect('/admin/media/picker?kind=image');
        self::assertSame(0, (int) $this->db->value("SELECT COUNT(*) FROM media_files WHERE original_name = 'upload-test-logo.svg'"));
        $this->get('/admin/media/picker');
        $this->assertSee('Chỉ nhận JPG, PNG, WebP, GIF, PDF, DOC, DOCX.');
    }

    public function test_requires_login(): void
    {
        $this->post('/admin/media/upload', [], ['files' => $this->fileField('upload-test-x.jpg', 'x')]);

        $this->assertRedirect('/admin/login?next=%2Fadmin%2Fmedia%2Fupload');
    }
}
