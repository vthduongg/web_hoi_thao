<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SortOrderService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SortOrderServiceTest extends TestCase
{
    public function test_assigns_sort_order_in_steps_of_ten(): void
    {
        $plan = SortOrderService::plan(['7', 3, 9], [3, 7, 9]);

        self::assertSame([7 => 10, 3 => 20, 9 => 30], $plan);
    }

    public function test_rejects_ids_outside_the_allowed_group(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SortOrderService::plan([3, 99], [3, 7]);
    }

    public function test_rejects_duplicate_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SortOrderService::plan([3, 3], [3, 7]);
    }

    public function test_rejects_non_numeric_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SortOrderService::plan(['3; DROP TABLE x'], [3]);
    }

    public function test_empty_list_gives_empty_plan(): void
    {
        self::assertSame([], SortOrderService::plan([], [1, 2]));
    }
}
