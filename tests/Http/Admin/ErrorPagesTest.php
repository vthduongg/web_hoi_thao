<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\App;
use App\Core\Router;
use Tests\Support\HttpTestCase;

final class ErrorPagesTest extends HttpTestCase
{
    public function test_unknown_admin_path_shows_vietnamese_404(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/khong-ton-tai');

        $this->assertStatus(404);
        $this->assertSee('<html lang="vi">');
        $this->assertSee('Không tìm thấy trang');
    }

    public function test_403_and_419_pages_are_vietnamese(): void
    {
        $this->loginAs('editor');
        $this->get('/admin/users');
        $this->assertStatus(403);
        $this->assertSee('<html lang="vi">');

        $this->send('POST', '/admin/logout');
        $this->assertStatus(419);
        $this->assertSee('<html lang="vi">');
    }

    public function test_exception_in_admin_controller_shows_vietnamese_500(): void
    {
        $router = new Router();
        $router->get('/admin/boom', '\\Tests\\Support\\BoomController@index');
        $this->app = new App($router, $this->app->services());

        $this->get('/admin/boom');

        $this->assertStatus(500);
        $this->assertSee('Đã có lỗi xảy ra');
        $this->assertDontSee('secret');
    }

    public function test_admin_csp_allows_inline_styles_but_public_does_not(): void
    {
        $admin = $this->get('/admin/login');
        $public = $this->get('/');

        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", $admin->headers['Content-Security-Policy']);
        self::assertStringNotContainsString('unsafe-inline', $public->headers['Content-Security-Policy']);
    }
}
