<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use App\Core\View;
use App\Repositories\UserRepository;

/**
 * Quên mật khẩu (FR-016): gửi liên kết dùng một lần, hết hạn sau 60 phút.
 *
 * Token gửi trong email là 32 byte ngẫu nhiên; CSDL chỉ lưu SHA-256 của token
 * nên lộ CSDL cũng không dùng được token.
 */
final class PasswordResetService
{
    public const EXPIRES_MINUTES = 60;

    public function __construct(
        private readonly Database $db,
        private readonly UserRepository $users,
        private readonly Mailer $mailer,
        private readonly string $appUrl,
    ) {
    }

    /** Gửi thư đặt lại mật khẩu nếu email thuộc tài khoản đang hoạt động; không báo gì nếu không có. */
    public function request(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !$user->isActive) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $this->db->insert('password_reset_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => Clock::now()->modify('+' . self::EXPIRES_MINUTES . ' minutes')->format('Y-m-d H:i:s'),
        ]);

        // Thư gửi từ khu vực quản trị nên ngôn ngữ hiện tại là tiếng Việt
        $link = rtrim($this->appUrl, '/') . '/admin/reset-password/' . $token;
        $body = View::current()->render('admin/emails/reset-password', ['user' => $user, 'link' => $link, 'minutes' => self::EXPIRES_MINUTES]);

        // Lỗi gửi thư chỉ ghi log: nếu hiện trang lỗi thì người ngoài biết được email này có tài khoản
        try {
            $this->mailer->send($user->email, t('auth.reset_email_subject'), $body);
        } catch (\Throwable $e) {
            error_log('Không gửi được thư đặt lại mật khẩu: ' . $e->getMessage());
        }
    }

    public function isValid(string $token): bool
    {
        return $this->findValidTokenRow($token) !== null;
    }

    /** @throws InvalidResetToken */
    public function reset(string $token, string $newPassword): void
    {
        $row = $this->findValidTokenRow($token) ?? throw new InvalidResetToken();

        $this->db->transaction(function () use ($row, $newPassword): void {
            $this->users->setPassword((int) $row['user_id'], $newPassword);
            $this->users->update((int) $row['user_id'], ['failed_login_count' => 0, 'locked_until' => null]);
            $this->users->incrementEpoch((int) $row['user_id']);
            $this->db->update('password_reset_tokens', ['used_at' => Clock::nowForDatabase()], 'id = ?', [$row['id']]);
        });
    }

    /** @return array<string, mixed>|null */
    private function findValidTokenRow(string $token): ?array
    {
        return $this->db->first(
            'SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?',
            [hash('sha256', $token), Clock::nowForDatabase()],
        );
    }
}
