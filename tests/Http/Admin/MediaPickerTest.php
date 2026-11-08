<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class MediaPickerTest extends HttpTestCase
{
    use CreatesContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createMedia(['original_name' => 'Ảnh hội trường.jpg', 'stored_path' => 'uploads/t/hall.jpg']);
        $this->createMedia(['original_name' => 'Hướng dẫn trình bày.pdf', 'stored_path' => 'uploads/t/guide.pdf', 'mime_type' => 'application/pdf', 'kind' => 'document']);
        $this->createMedia(['original_name' => 'Đã xoá.jpg', 'stored_path' => 'uploads/t/deleted.jpg', 'deleted_at' => '2026-01-01']);
    }

    public function test_lists_images_and_documents_except_deleted(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/media/picker');

        $this->assertStatus(200);
        $this->assertSee('Ảnh hội trường.jpg');
        $this->assertSee('Hướng dẫn trình bày.pdf');
        $this->assertDontSee('Đã xoá.jpg');
        $this->assertSee('data-media-url="/uploads/t/hall.jpg"');
    }

    public function test_searches_by_vietnamese_name(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/media/picker?q=' . rawurlencode('hội trường'));

        $this->assertSee('Ảnh hội trường.jpg');
        $this->assertDontSee('Hướng dẫn trình bày.pdf');
    }

    public function test_search_treats_percent_underscore_and_bracket_literally(): void
    {
        $this->createMedia(['original_name' => 'Tỉ lệ 100%_xong [bản 1].pdf', 'stored_path' => 'uploads/t/a.pdf', 'kind' => 'document', 'mime_type' => 'application/pdf']);
        $this->createMedia(['original_name' => 'Tỉ lệ 100abxong.pdf', 'stored_path' => 'uploads/t/b.pdf', 'kind' => 'document', 'mime_type' => 'application/pdf']);
        $this->loginAs('editor');

        $this->get('/admin/media/picker?q=' . rawurlencode('100%_xong [bản'));

        $this->assertSee('Tỉ lệ 100%_xong [bản 1].pdf');
        $this->assertDontSee('Tỉ lệ 100abxong.pdf');
    }

    public function test_filters_by_kind(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/media/picker?kind=image');

        $this->assertSee('Ảnh hội trường.jpg');
        $this->assertDontSee('Hướng dẫn trình bày.pdf');
    }

    public function test_requires_login(): void
    {
        $this->get('/admin/media/picker');

        $this->assertRedirect('/admin/login?next=%2Fadmin%2Fmedia%2Fpicker');
    }
}
