<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use DateTimeImmutable;

/**
 * Khoá tạm tài khoản khi đăng nhập sai nhiều lần (FR-017): sai 5 lần liên tiếp → khoá 15 phút.
 * Các hàm trả về cột cần cập nhật trong bảng users.
 */
final class LoginThrottle
{
    public const MAX_FAILURES = 5;
    public const LOCK_MINUTES = 15;

    public static function isLocked(User $user, DateTimeImmutable $now): bool
    {
        return $user->lockedUntil !== null && $user->lockedUntil > $now->format('Y-m-d H:i:s');
    }

    /** @return array{failed_login_count: int, locked_until: ?string} */
    public static function afterFailure(User $user, DateTimeImmutable $now): array
    {
        $failures = $user->failedLoginCount + 1;
        if ($failures >= self::MAX_FAILURES) {
            return [
                'failed_login_count' => 0,
                'locked_until' => $now->modify('+' . self::LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s'),
            ];
        }

        return ['failed_login_count' => $failures, 'locked_until' => null];
    }

    /** @return array{failed_login_count: int, locked_until: null} */
    public static function afterSuccess(): array
    {
        return ['failed_login_count' => 0, 'locked_until' => null];
    }
}
