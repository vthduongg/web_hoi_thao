<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\LoginThrottle;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class LoginThrottleTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-03-10 08:00:00', new DateTimeZone('UTC'));
    }

    private static function user(int $failed = 0, ?string $lockedUntil = null): User
    {
        return new User(1, 'a@b.vn', 'A', 'hash', 'editor', true, $failed, $lockedUntil, 0, null);
    }

    public function test_first_four_failures_only_increase_the_counter(): void
    {
        $changes = LoginThrottle::afterFailure(self::user(failed: 3), $this->now);

        self::assertSame(['failed_login_count' => 4, 'locked_until' => null], $changes);
    }

    public function test_fifth_failure_locks_for_fifteen_minutes(): void
    {
        $changes = LoginThrottle::afterFailure(self::user(failed: 4), $this->now);

        self::assertSame(['failed_login_count' => 0, 'locked_until' => '2026-03-10 08:15:00'], $changes);
    }

    public function test_is_locked_until_the_lock_time_passes(): void
    {
        $user = self::user(lockedUntil: '2026-03-10 08:15:00');

        self::assertTrue(LoginThrottle::isLocked($user, $this->now));
        self::assertTrue(LoginThrottle::isLocked($user, $this->now->modify('+14 minutes 59 seconds')));
        self::assertFalse(LoginThrottle::isLocked($user, $this->now->modify('+15 minutes')));
        self::assertFalse(LoginThrottle::isLocked(self::user(), $this->now));
    }

    public function test_success_resets_counter_and_lock(): void
    {
        self::assertSame(['failed_login_count' => 0, 'locked_until' => null], LoginThrottle::afterSuccess());
    }
}
