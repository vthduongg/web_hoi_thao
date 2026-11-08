<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Core\Clock;
use App\Models\ImportantDate;
use App\Services\UpcomingDateFinder;
use PHPUnit\Framework\TestCase;

final class UpcomingDateFinderTest extends TestCase
{
    protected function tearDown(): void
    {
        Clock::reset();
    }

    private static function date(int $id, ?string $start, string $status = 'normal', int $sort = 10, bool $visible = true): ImportantDate
    {
        return new ImportantDate(
            id: $id,
            conferenceId: 1,
            label: "Mốc $id",
            status: $status,
            dateStart: $start,
            dateEnd: null,
            originalDate: null,
            specialRole: null,
            sortOrder: $sort,
            isVisible: $visible,
        );
    }

    public function test_picks_the_earliest_date_from_today_onwards(): void
    {
        Clock::freeze('2026-03-10 03:00:00'); // 10:00 ngày 10/03 giờ VN

        $found = UpcomingDateFinder::find([
            self::date(1, '2026-03-01'),
            self::date(2, '2026-04-20'),
            self::date(3, '2026-03-25'),
        ]);

        self::assertSame(3, $found?->id);
    }

    public function test_a_deadline_today_is_still_upcoming(): void
    {
        Clock::freeze('2026-03-10 03:00:00');

        self::assertSame(1, UpcomingDateFinder::find([self::date(1, '2026-03-10'), self::date(2, '2026-03-11')])?->id);
    }

    public function test_uses_vietnam_day_boundary(): void
    {
        $dates = [self::date(1, '2026-03-10'), self::date(2, '2026-03-11')];

        Clock::freeze('2026-03-10 16:59:00'); // 23:59 ngày 10 giờ VN
        self::assertSame(1, UpcomingDateFinder::find($dates)?->id);

        Clock::freeze('2026-03-10 17:00:00'); // 00:00 ngày 11 giờ VN
        self::assertSame(2, UpcomingDateFinder::find($dates)?->id);
    }

    public function test_ties_are_broken_by_sort_order(): void
    {
        Clock::freeze('2026-03-01 00:00:00');

        $found = UpcomingDateFinder::find([self::date(1, '2026-05-01', sort: 30), self::date(2, '2026-05-01', sort: 20)]);

        self::assertSame(2, $found?->id);
    }

    public function test_ignores_tba_and_hidden_dates(): void
    {
        Clock::freeze('2026-03-01 00:00:00');

        $found = UpcomingDateFinder::find([
            self::date(1, null, 'tba'),
            self::date(2, '2026-03-05', visible: false),
            self::date(3, '2026-04-01'),
        ]);

        self::assertSame(3, $found?->id);
    }

    public function test_extended_date_uses_its_new_date(): void
    {
        Clock::freeze('2026-03-01 00:00:00');

        self::assertSame(1, UpcomingDateFinder::find([self::date(1, '2026-03-15', 'extended'), self::date(2, '2026-03-20')])?->id);
    }

    public function test_returns_null_when_all_dates_have_passed(): void
    {
        Clock::freeze('2027-01-01 00:00:00');

        self::assertNull(UpcomingDateFinder::find([self::date(1, '2026-03-01'), self::date(2, '2026-12-31')]));
        self::assertNull(UpcomingDateFinder::find([]));
    }
}
