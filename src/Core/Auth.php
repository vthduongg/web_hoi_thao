<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;
use App\Services\LoginThrottle;

/**
 * Đăng nhập và phân quyền trang quản trị (FR-016 – FR-020).
 *
 * Session lưu: id người dùng, "epoch" (số phiên bản phiên) và thời điểm hoạt động cuối.
 * Mỗi request kiểm tra lại: còn trong 30 phút, tài khoản còn hoạt động, epoch còn khớp
 * (đổi mật khẩu hoặc bị khoá sẽ tăng epoch → mọi phiên cũ hết hiệu lực).
 */
final class Auth
{
    public const IDLE_TIMEOUT_MINUTES = 30;

    /** Quyền của route: 'A' = chỉ quản trị viên, 'E' = quản trị viên và biên tập viên. */
    public const PERMISSION_ADMIN = 'A';
    public const PERMISSION_EDITOR = 'E';

    private const KEY_USER = 'auth_user_id';
    private const KEY_EPOCH = 'auth_epoch';
    private const KEY_LAST_ACTIVITY = 'auth_last_activity';

    private readonly UserRepository $users;
    private ?User $current = null;
    private bool $checked = false;

    public function __construct(Database $db, private readonly Session $session, private readonly ActivityLogger $logger)
    {
        $this->users = new UserRepository($db);
    }

    public function attempt(string $email, string $password): LoginResult
    {
        $user = $this->users->findByEmail($email);
        $now = Clock::now();

        if ($user === null) {
            $this->logger->log(null, 'login_failed', 'users', null, UserRepository::normalizeEmail($email));

            return LoginResult::InvalidCredentials;
        }
        if (!$user->isActive) {
            return LoginResult::Inactive;
        }
        if (LoginThrottle::isLocked($user, $now)) {
            return LoginResult::Locked;
        }
        if (!password_verify($password, $user->passwordHash)) {
            $changes = LoginThrottle::afterFailure($user, $now);
            $this->users->update($user->id, $changes);
            $this->logger->log($user->id, 'login_failed', 'users', $user->id, $user->email);

            return $changes['locked_until'] !== null ? LoginResult::Locked : LoginResult::InvalidCredentials;
        }

        $this->users->update($user->id, LoginThrottle::afterSuccess() + ['last_login_at' => Clock::nowForDatabase()]);
        $this->session->regenerate();
        $this->loginById($user->id);
        $this->logger->log($user->id, 'login', 'users', $user->id, $user->email);

        return LoginResult::Success;
    }

    /** Ghi phiên đăng nhập cho người dùng (không kiểm tra mật khẩu). */
    public function loginById(int $userId): void
    {
        $user = $this->users->find($userId) ?? throw new \InvalidArgumentException("Không có người dùng $userId");
        $this->session->set(self::KEY_USER, $user->id);
        $this->session->set(self::KEY_EPOCH, $user->sessionEpoch);
        $this->session->set(self::KEY_LAST_ACTIVITY, Clock::now()->getTimestamp());
        $this->current = $user;
        $this->checked = true;
    }

    /** Gọi đầu mỗi request: bỏ kết quả kiểm tra của request trước. */
    public function beginRequest(): void
    {
        $this->checked = false;
        $this->current = null;
    }

    /** Người đang đăng nhập (đã kiểm tra hết hạn, bị khoá, epoch), hoặc null. */
    public function user(): ?User
    {
        if ($this->checked) {
            return $this->current;
        }
        $this->checked = true;

        $userId = $this->session->get(self::KEY_USER);
        if ($userId === null) {
            return null;
        }

        $user = $this->users->find((int) $userId);
        $idleSeconds = Clock::now()->getTimestamp() - (int) $this->session->get(self::KEY_LAST_ACTIVITY, 0);
        $valid = $user !== null
            && $user->isActive
            && $user->sessionEpoch === (int) $this->session->get(self::KEY_EPOCH)
            && $idleSeconds <= self::IDLE_TIMEOUT_MINUTES * 60;

        if (!$valid) {
            $this->clearSession();

            return null;
        }

        $this->session->set(self::KEY_LAST_ACTIVITY, Clock::now()->getTimestamp());

        return $this->current = $user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function can(string $permission): bool
    {
        $user = $this->user();

        return match ($permission) {
            self::PERMISSION_ADMIN => $user?->isAdmin() ?? false,
            self::PERMISSION_EDITOR => $user !== null,
            default => throw new \InvalidArgumentException("Quyền không hợp lệ: $permission"),
        };
    }

    /** Sau khi chính người dùng đổi mật khẩu: giữ phiên hiện tại, các phiên khác hết hiệu lực. */
    public function refreshEpoch(int $newEpoch): void
    {
        $this->session->set(self::KEY_EPOCH, $newEpoch);
    }

    public function logout(): void
    {
        $user = $this->user();
        if ($user !== null) {
            $this->logger->log($user->id, 'logout', 'users', $user->id, $user->email);
        }
        $this->clearSession();
        $this->session->regenerate();
    }

    private function clearSession(): void
    {
        $this->session->forget(self::KEY_USER);
        $this->session->forget(self::KEY_EPOCH);
        $this->session->forget(self::KEY_LAST_ACTIVITY);
        $this->current = null;
    }
}
