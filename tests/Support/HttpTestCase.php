<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\AppFactory;
use App\Core\ArraySessionStore;
use App\Core\Request;
use App\Core\Response;

/**
 * Lớp cha cho test HTTP: gọi thẳng App::handle() với request giả, không cần web server.
 *
 * Session được giữ giữa các request trong cùng một test (giống trình duyệt giữ cookie).
 */
abstract class HttpTestCase extends DatabaseTestCase
{
    protected App $app;
    protected ArraySessionStore $sessionStore;
    protected ?Response $lastResponse = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionStore = new ArraySessionStore();
        $this->app = AppFactory::create(dirname(__DIR__, 2), $this->db, $this->sessionStore);
    }

    protected function get(string $uri): Response
    {
        return $this->send('GET', $uri);
    }

    /** POST kèm token CSRF hợp lệ (trừ khi truyền sẵn `_token`). */
    protected function post(string $uri, array $data = [], array $files = []): Response
    {
        if (!array_key_exists('_token', $data)) {
            $data['_token'] = $this->app->services()->csrf->token();
        }

        return $this->send('POST', $uri, $data, $files);
    }

    protected function send(string $method, string $uri, array $post = [], array $files = []): Response
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $request = new Request($method, $path, $query, $post, $files, [], '127.0.0.1');
        $this->lastResponse = $this->app->handle($request);

        return $this->lastResponse;
    }

    /** Đăng nhập trực tiếp bằng vai trò; tạo tài khoản nếu chưa có. Trả về id người dùng. */
    protected function loginAs(string $role): int
    {
        $email = $role . '-' . bin2hex(random_bytes(4)) . '@test.local';
        $userId = $this->db->insert('users', [
            'email' => $email,
            'full_name' => 'Người dùng ' . $role,
            'password_hash' => password_hash('Test@12345', PASSWORD_DEFAULT),
            'role' => $role,
            'is_active' => 1,
            'failed_login_count' => 0,
            'session_epoch' => 0,
        ]);
        $this->app->services()->auth->loginById($userId);

        return $userId;
    }

    protected function assertStatus(int $expected, ?Response $response = null): void
    {
        $response ??= $this->lastResponse;
        self::assertSame($expected, $response->status, 'Mã trạng thái không đúng. Nội dung: ' . mb_substr($response->body, 0, 500));
    }

    protected function assertRedirect(string $expectedLocation, ?Response $response = null): void
    {
        $response ??= $this->lastResponse;
        self::assertContains($response->status, [301, 302, 303], 'Không phải chuyển hướng: ' . $response->status);
        self::assertSame($expectedLocation, $response->headers['Location'] ?? null);
    }

    protected function assertSee(string $text, ?Response $response = null): void
    {
        $response ??= $this->lastResponse;
        self::assertStringContainsString($text, $response->body);
    }

    protected function assertDontSee(string $text, ?Response $response = null): void
    {
        $response ??= $this->lastResponse;
        self::assertStringNotContainsString($text, $response->body);
    }
}
