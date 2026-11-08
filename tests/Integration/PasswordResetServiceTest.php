<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Core\Lang;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\InvalidResetToken;
use App\Services\Mailer;
use App\Services\PasswordResetService;
use Tests\Support\DatabaseTestCase;

final class PasswordResetServiceTest extends DatabaseTestCase
{
    private PasswordResetService $service;
    private Mailer $mailer;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 08:00:00');
        $root = dirname(__DIR__, 2);
        new View($root . '/templates');
        Lang::setCurrent(Lang::load('vi', $root . '/lang'));
        $this->mailer = Mailer::fake();
        $users = new UserRepository($this->db);
        $this->service = new PasswordResetService($this->db, $users, $this->mailer, 'http://localhost:8000');
        $this->userId = $users->create('Reset@Example.com', 'Người dùng', 'OldPass123', 'editor');
    }

    private function tokenFromEmail(): string
    {
        self::assertCount(1, $this->mailer->sent);
        preg_match('#/admin/reset-password/([a-f0-9]+)#', $this->mailer->sent[0]['body'], $m);

        return $m[1];
    }

    public function test_request_sends_link_with_64_character_token_and_stores_only_hash(): void
    {
        $this->service->request('reset@example.com');

        $token = $this->tokenFromEmail();
        self::assertSame(64, strlen($token));
        self::assertSame('reset@example.com', $this->mailer->sent[0]['to']);
        $row = $this->db->first('SELECT token_hash, expires_at FROM password_reset_tokens WHERE user_id = ?', [$this->userId]);
        self::assertSame(hash('sha256', $token), $row['token_hash']);
        self::assertStringStartsWith('2026-03-10 09:00:00', (string) $row['expires_at']);
    }

    public function test_unknown_or_inactive_email_sends_nothing(): void
    {
        $this->service->request('nobody@example.com');
        $this->db->update('users', ['is_active' => 0], 'id = ?', [$this->userId]);
        $this->service->request('reset@example.com');

        self::assertSame([], $this->mailer->sent);
    }

    public function test_reset_changes_password_and_increments_session_epoch(): void
    {
        $this->service->request('reset@example.com');
        $epochBefore = (int) $this->db->value('SELECT session_epoch FROM users WHERE id = ?', [$this->userId]);

        $this->service->reset($this->tokenFromEmail(), 'NewPass456');

        $user = $this->db->first('SELECT password_hash, session_epoch FROM users WHERE id = ?', [$this->userId]);
        self::assertTrue(password_verify('NewPass456', $user['password_hash']));
        self::assertSame($epochBefore + 1, (int) $user['session_epoch']);
    }

    public function test_token_can_be_used_only_once(): void
    {
        $this->service->request('reset@example.com');
        $token = $this->tokenFromEmail();
        $this->service->reset($token, 'NewPass456');

        $this->expectException(InvalidResetToken::class);
        $this->service->reset($token, 'Another789');
    }

    public function test_token_expires_after_sixty_minutes(): void
    {
        $this->service->request('reset@example.com');
        $token = $this->tokenFromEmail();
        self::assertTrue($this->service->isValid($token));

        Clock::freeze('2026-03-10 09:00:00');
        self::assertFalse($this->service->isValid($token));
        $this->expectException(InvalidResetToken::class);
        $this->service->reset($token, 'NewPass456');
    }
}
