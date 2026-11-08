<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MenuItem;
use App\Models\Page;
use App\Services\RelatedPagesFinder;
use PHPUnit\Framework\TestCase;

final class RelatedPagesFinderTest extends TestCase
{
    private static function page(int $id): Page
    {
        return new Page(
            id: $id,
            conferenceId: 1,
            title: "Page $id",
            slug: "page-$id",
            bodyHtml: '',
            module: 'none',
            seoTitle: null,
            seoDescription: null,
            featuredMediaId: null,
            status: 'published',
            publishedAt: '2020-01-01 00:00:00',
            version: 1,
        );
    }

    private static function menu(int $id, ?int $parentId, ?int $pageId, int $sort): MenuItem
    {
        return new MenuItem($id, 1, $parentId, "Item $id", $pageId, null, $sort);
    }

    /** @param list<Page> $pages */
    private static function ids(array $pages): array
    {
        return array_map(static fn (Page $p): int => $p->id, $pages);
    }

    public function test_pages_sharing_a_tag_come_first_then_same_menu_group(): void
    {
        $pages = array_map([self::class, 'page'], [1, 2, 3, 4, 5]);
        $tags = [1 => ['deadline'], 4 => ['deadline'], 5 => ['other']];
        // Nhóm "Conference" (id 100) chứa trang 1, 2, 3; trang 4, 5 ở nhóm khác
        $menu = [
            self::menu(100, null, null, 10),
            self::menu(101, 100, 1, 10),
            self::menu(102, 100, 2, 20),
            self::menu(103, 100, 3, 30),
            self::menu(200, null, 4, 20),
            self::menu(300, null, 5, 30),
        ];

        $related = RelatedPagesFinder::find($pages[0], $pages, $tags, $menu);

        self::assertSame([4, 2, 3], self::ids($related));
    }

    public function test_excludes_current_page_and_limits_to_six(): void
    {
        $pages = array_map([self::class, 'page'], range(1, 10));
        $tags = array_fill_keys(range(1, 10), ['all']);

        $related = RelatedPagesFinder::find($pages[0], $pages, $tags, []);

        self::assertSame([2, 3, 4, 5, 6, 7], self::ids($related));
    }

    public function test_menu_group_is_the_top_level_item_of_the_first_menu_entry(): void
    {
        $pages = array_map([self::class, 'page'], [1, 2, 3]);
        // Trang 1 xuất hiện ở nhóm A (thứ tự 10) và nhóm B (thứ tự 20) — lấy nhóm A
        $menu = [
            self::menu(10, null, null, 10),
            self::menu(11, 10, 1, 10),
            self::menu(12, 10, 2, 20),
            self::menu(20, null, null, 20),
            self::menu(21, 20, 1, 10),
            self::menu(22, 20, 3, 20),
        ];

        self::assertSame([2], self::ids(RelatedPagesFinder::find($pages[0], $pages, [], $menu)));
    }

    public function test_page_not_in_menu_only_uses_tags(): void
    {
        $pages = array_map([self::class, 'page'], [1, 2, 3]);
        $menu = [self::menu(10, null, null, 10), self::menu(11, 10, 2, 10), self::menu(12, 10, 3, 20)];

        self::assertSame([3], self::ids(RelatedPagesFinder::find($pages[0], $pages, [1 => ['x'], 3 => ['x']], $menu)));
    }

    public function test_returns_empty_when_nothing_related(): void
    {
        $pages = array_map([self::class, 'page'], [1, 2]);

        self::assertSame([], RelatedPagesFinder::find($pages[0], $pages, [], []));
    }

    public function test_only_considers_given_public_pages(): void
    {
        $pages = array_map([self::class, 'page'], [1, 2]);
        $menu = [self::menu(10, null, null, 10), self::menu(11, 10, 1, 10), self::menu(12, 10, 9, 20)];

        // Trang 9 nằm trong menu nhưng không công khai (không có trong $pages)
        self::assertSame([], RelatedPagesFinder::find($pages[0], $pages, [], $menu));
    }
}
