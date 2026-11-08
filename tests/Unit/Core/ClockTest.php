<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Clock;
use PHPUnit\Framework\TestCase;

final class ClockTest extends TestCase
{
    protected function tearDown(): void
    {
        Clock::reset();
    }

    public function test_defaults_to_real_utc_time(): void
    {
        $now = Clock::now();

        self::assertSame('UTC', $now->getTimezone()->getName());
        self::assertLessThan(5, abs($now->getTimestamp() - time()));
    }

    public function test_freeze_fixes_the_time(): void
    {
        Clock::freeze('2026-03-10 08:00:00');

        self::assertSame('2026-03-10 08:00:00', Clock::now()->format('Y-m-d H:i:s'));
        self::assertSame('UTC', Clock::now()->getTimezone()->getName());
    }

    public function test_reset_returns_to_real_time(): void
    {
        Clock::freeze('2000-01-01 00:00:00');
        Clock::reset();

        self::assertGreaterThan(2025, (int) Clock::now()->format('Y'));
    }

    public function test_today_in_vietnam(): void
    {
        Clock::freeze('2026-03-10 16:59:00'); // 23:59 giờ VN
        self::assertSame('2026-03-10', Clock::todayInVietnam());

        Clock::freeze('2026-03-10 17:00:00'); // 00:00 ngày 11 giờ VN
        self::assertSame('2026-03-11', Clock::todayInVietnam());
    }
}
