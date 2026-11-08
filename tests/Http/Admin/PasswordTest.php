<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Core\Clock;
use App\Repositories\UserRepository;
use Tests\Support\HttpTestCase;

final class PasswordTest extends HttpTestCase
{
    private const SAME_MESSAGE = 'Nếu email có trong hệ thống, bạn sẽ nhận được thư hướng dẫn đặt lại mật khẩu.';

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 08:00:00');
        (new UserRepository($this->db))->create('known@example.com', 'Người dùng', 'Secret123', 'editor');
    }

    private function sentTokens(): array
    {
        $tokens = [];
        foreach ($this->app->services()->mailer->sent as $mail) {
            preg_match('#/admin/reset-password/([a-f0-9]+)#', $mail['body'], $m);
            $tokens[] = $m[1];
        }

        return $tokens;
    }

    public function test_forgot_password_shows_same_message_for_known_and_unknown_email(): void
    {
        $this->post('/admin/forgot-password', ['email' => 'known@example.com']);
        $known = $this->lastResponse->body;
        $this->post('/admin/forgot-password', ['email' => 'unknown@example.com']);

        $this->assertSee(self::SAME_MESSAGE);
        self::assertStringContainsString(self::SAME_MESSAGE, $known);
        self::assertCount(1, $this->app->services()->mailer->sent);
    }

    public function test_reset_with_valid_token_then_login_with_new_password(): void
    {
        $this->post('/admin/forgot-password', ['email' => 'known@example.com']);
        $token = $this->sentTokens()[0];

        $this->get("/admin/reset-password/$token");
        $this->assertStatus(200);
        $this->post("/admin/reset-password/$token", ['password' => 'BrandNew99', 'password_confirmation' => 'BrandNew99']);

        $this->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => 'known@example.com', 'password' => 'BrandNew99']);
        $this->assertRedirect('/admin');
    }

    public function test_used_or_expired_token_shows_error(): void
    {
        $this->post('/admin/forgot-password', ['email' => 'known@example.com']);
        $token = $this->sentTokens()[0];
        $this->post("/admin/reset-password/$token", ['password' => 'BrandNew99', 'password_confirmation' => 'BrandNew99']);

        $this->get("/admin/reset-password/$token");
        $this->assertStatus(410);
        $this->assertSee('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');

        $this->post('/admin/forgot-password', ['email' => 'known@example.com']);
        $second = $this->sentTokens()[1];
        Clock::freeze('2026-03-10 09:00:01');
        $this->get("/admin/reset-password/$second");
        $this->assertStatus(410);
    }

    public function test_reset_rejects_short_or_mismatched_password(): void
    {
        $this->post('/admin/forgot-password', ['email' => 'known@example.com']);
        $token = $this->sentTokens()[0];

        $this->post("/admin/reset-password/$token", ['password' => 'short', 'password_confirmation' => 'short']);
        $this->assertStatus(422);
        $this->assertSee('Tối thiểu 8 ký tự.');

        $this->post("/admin/reset-password/$token", ['password' => 'LongEnough1', 'password_confirmation' => 'Different1']);
        $this->assertStatus(422);
        $this->assertSee('Mật khẩu nhập lại không khớp.');
    }

    public function test_change_own_password(): void
    {
        $this->post('/admin/login', ['email' => 'known@example.com', 'password' => 'Secret123']);

        $this->post('/admin/profile/password', ['current_password' => 'wrong', 'password' => 'NewSecret1', 'password_confirmation' => 'NewSecret1']);
        $this->assertStatus(422);
        $this->assertSee('Mật khẩu hiện tại không đúng.');

        $this->post('/admin/profile/password', ['current_password' => 'Secret123', 'password' => 'short', 'password_confirmation' => 'short']);
        $this->assertStatus(422);
        $this->assertSee('Tối thiểu 8 ký tự.');

        $this->post('/admin/profile/password', ['current_password' => 'Secret123', 'password' => 'NewSecret1', 'password_confirmation' => 'NewSecret1']);
        $this->assertRedirect('/admin/profile/password');
        $this->get('/admin');
        $this->assertStatus(200); // phiên hiện tại vẫn còn

        $hash = $this->db->value("SELECT password_hash FROM users WHERE email = 'known@example.com'");
        self::assertTrue(password_verify('NewSecret1', $hash));
    }
}
