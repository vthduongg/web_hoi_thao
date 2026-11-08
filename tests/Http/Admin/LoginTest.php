<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use App\Repositories\UserRepository;
use Tests\Support\HttpTestCase;

final class LoginTest extends HttpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 08:00:00');
        (new UserRepository($this->db))->create('editor@example.com', 'Biên tập viên', 'Secret123', 'editor');
    }

    private function login(string $password = 'Secret123', string $email = 'Editor@Example.com', string $next = '')
    {
        return $this->post('/admin/login' . ($next !== '' ? '?next=' . rawurlencode($next) : ''), ['email' => $email, 'password' => $password]);
    }

    public function test_guest_is_redirected_to_login_with_next(): void
    {
        $this->get('/admin');

        $this->assertRedirect('/admin/login?next=%2Fadmin');
    }

    public function test_login_page_is_vietnamese(): void
    {
        $this->get('/admin/login');

        $this->assertStatus(200);
        $this->assertSee('<html lang="vi">');
        $this->assertSee('Đăng nhập');
        $this->assertSee('name="_token"');
    }

    public function test_successful_login_regenerates_session_and_redirects(): void
    {
        $this->login();

        $this->assertRedirect('/admin');
        self::assertSame(1, $this->sessionStore->regenerateCount, 'Session id phải đổi sau khi đăng nhập');
        $this->get('/admin');
        $this->assertStatus(200);
        $this->assertSee('Biên tập viên');
    }

    public function test_redirects_back_to_next_admin_path_only(): void
    {
        $this->login(next: '/admin/pages');
        $this->assertRedirect('/admin/pages');

        $this->post('/admin/logout');
        $this->login(next: 'https://evil.example.com');
        $this->assertRedirect('/admin');
    }

    public function test_wrong_password_shows_vietnamese_error(): void
    {
        $this->login('wrong-pass');

        $this->assertStatus(422);
        $this->assertSee('Email hoặc mật khẩu không đúng.');
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'login_failed'"));
    }

    public function test_locked_after_five_failures_even_with_correct_password(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->login('wrong-pass');
        }

        $this->login('Secret123');

        $this->assertStatus(422);
        $this->assertSee('Tài khoản tạm khoá 15 phút do đăng nhập sai nhiều lần.');

        Clock::freeze('2026-03-10 08:15:01');
        $this->login('Secret123');
        $this->assertRedirect('/admin');
    }

    public function test_session_expires_after_thirty_minutes_of_inactivity(): void
    {
        $this->login();
        Clock::freeze('2026-03-10 08:29:00');
        $this->get('/admin');
        $this->assertStatus(200);

        Clock::freeze('2026-03-10 08:59:30'); // 30 phút 30 giây sau lần hoạt động cuối
        $this->get('/admin');

        $this->assertRedirect('/admin/login?next=%2Fadmin');
    }

    public function test_logout_ends_session(): void
    {
        $this->login();

        $this->post('/admin/logout');

        $this->assertRedirect('/admin/login');
        $this->get('/admin');
        $this->assertRedirect('/admin/login?next=%2Fadmin');
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'logout'"));
    }

    public function test_logged_in_user_visiting_login_goes_to_dashboard(): void
    {
        $this->login();

        $this->get('/admin/login');

        $this->assertRedirect('/admin');
    }
}
