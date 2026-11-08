<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\HttpException;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', 'HomeController@index');
        $this->router->get('/{year}/{slug}', 'ArchiveController@page');
        $this->router->get('/{slug}', 'PageController@show');
        $this->router->post('/admin/pages/{id}', 'Admin\\PageController@update', 'E');
    }

    public function test_matches_root(): void
    {
        $match = $this->router->match('GET', '/');

        self::assertSame('HomeController@index', $match->handler);
        self::assertSame([], $match->params);
    }

    public function test_matches_slug_parameter(): void
    {
        $match = $this->router->match('GET', '/call-for-papers');

        self::assertSame('PageController@show', $match->handler);
        self::assertSame(['slug' => 'call-for-papers'], $match->params);
    }

    public function test_year_must_have_four_digits(): void
    {
        $match = $this->router->match('GET', '/2025/important-dates');
        self::assertSame('ArchiveController@page', $match->handler);
        self::assertSame(['year' => '2025', 'slug' => 'important-dates'], $match->params);

        $this->expectException(HttpException::class);
        $this->router->match('GET', '/25/important-dates');
    }

    public function test_slug_rejects_uppercase_and_other_characters(): void
    {
        try {
            $this->router->match('GET', '/Call_For_Papers');
            self::fail('Phải ném lỗi 404');
        } catch (HttpException $e) {
            self::assertSame(404, $e->status);
        }
    }

    public function test_returns_404_when_nothing_matches(): void
    {
        try {
            $this->router->match('GET', '/a/b/c/d');
            self::fail('Phải ném lỗi 404');
        } catch (HttpException $e) {
            self::assertSame(404, $e->status);
        }
    }

    public function test_returns_405_for_wrong_method(): void
    {
        try {
            $this->router->match('GET', '/admin/pages/5');
            self::fail('Phải ném lỗi 405');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status);
        }
    }

    public function test_keeps_permission_and_numeric_id(): void
    {
        $match = $this->router->match('POST', '/admin/pages/5');

        self::assertSame('E', $match->permission);
        self::assertSame(['id' => '5'], $match->params);
    }

    public function test_ignores_trailing_slash(): void
    {
        self::assertSame('PageController@show', $this->router->match('GET', '/contact/')->handler);
    }
}
