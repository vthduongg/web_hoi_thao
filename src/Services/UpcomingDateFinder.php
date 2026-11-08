<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Models\ImportantDate;

/**
 * Tìm "mốc sắp tới" để làm nổi bật trên trang Important Dates.
 *
 * Quy tắc (data-model.md): mốc đang hiện, không phải "sẽ thông báo sau", có ngày từ hôm nay (giờ Việt Nam)
 * trở đi; lấy mốc sớm nhất, nếu trùng ngày thì lấy mốc có thứ tự nhỏ hơn.
 */
final class UpcomingDateFinder
{
    /** @param list<ImportantDate> $dates */
    public static function find(array $dates): ?ImportantDate
    {
        $today = Clock::todayInVietnam();
        $best = null;

        foreach ($dates as $date) {
            if (!$date->isVisible || $date->isTba() || $date->dateStart === null || $date->dateStart < $today) {
                continue;
            }
            if ($best === null || [$date->dateStart, $date->sortOrder] < [$best->dateStart, $best->sortOrder]) {
                $best = $date;
            }
        }

        return $best;
    }
}
