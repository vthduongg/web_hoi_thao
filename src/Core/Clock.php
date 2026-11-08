<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Nguồn "thời điểm hiện tại" duy nhất của ứng dụng.
 *
 * Mọi code cần giờ hiện tại phải gọi Clock::now() để test có thể cố định thời gian bằng Clock::freeze().
 * Giá trị luôn ở múi giờ UTC (giống cách lưu trong CSDL).
 */
final class Clock
{
    public const VIETNAM_TIMEZONE = 'Asia/Ho_Chi_Minh';

    private static ?DateTimeImmutable $frozen = null;

    public static function now(): DateTimeImmutable
    {
        return self::$frozen ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /** Cố định thời gian (chỉ dùng trong test). Chuỗi được hiểu là giờ UTC. */
    public static function freeze(string $utcDateTime): void
    {
        self::$frozen = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
    }

    public static function reset(): void
    {
        self::$frozen = null;
    }

    /** Ngày hôm nay theo giờ Việt Nam, dạng Y-m-d. */
    public static function todayInVietnam(): string
    {
        return self::now()->setTimezone(new DateTimeZone(self::VIETNAM_TIMEZONE))->format('Y-m-d');
    }

    /** Thời điểm hiện tại dạng chuỗi để ghi vào cột DATETIME2. */
    public static function nowForDatabase(): string
    {
        return self::now()->format('Y-m-d H:i:s');
    }
}
