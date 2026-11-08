<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Core\Router;
use Tests\Support\HttpTestCase;

final class ErrorPagesTest extends HttpTestCase
{
    public function test_unknown_public_path_shows_english_404(): void
    {
        $response = $this->get('/khong/ton/tai/that');

        $this->assertStatus(404);
        $this->assertSee('<html lang="en">');
        $this->assertSee('Page not found');
        $this->assertDontSee('Không tìm thấy');
        self::assertSame('text/html; charset=UTF-8', $response->headers['Content-Type']);
    }

    public function test_exception_in_controller_shows_english_500_without_details(): void
    {
        $this->addRoute(static function (Router $r): void {
            $r->get('/boom', '\\Tests\\Support\\BoomController@index');
        });

        $this->get('/boom');

        $this->assertStatus(500);
        $this->assertSee('Something went wrong');
        $this->assertDontSee('Class');
        $this->assertDontSee('Stack');
    }

    public function test_public_responses_have_security_headers(): void
    {
        $response = $this->get('/khong-co');

        self::assertSame(
            "default-src 'self'; img-src 'self' data:; frame-src https://www.youtube-nocookie.com https://www.youtube.com https://www.google.com; script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'",
            $response->headers['Content-Security-Policy'],
        );
        self::assertSame('nosniff', $response->headers['X-Content-Type-Options']);
        self::assertSame('strict-origin-when-cross-origin', $response->headers['Referrer-Policy']);
        self::assertStringNotContainsString('unsafe-inline', $response->headers['Content-Security-Policy']);
    }

    /** Tạo lại App với thêm route chỉ dùng trong test. */
    private function addRoute(callable $register): void
    {
        $router = new Router();
        $register($router);
        $this->app = new \App\Core\App($router, $this->app->services());
    }
}
