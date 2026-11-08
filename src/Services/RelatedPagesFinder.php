<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Page;

/**
 * Chọn tối đa 6 trang cho khối "Related pages" (FR-013).
 *
 * Thứ tự ưu tiên: (1) trang có chung ít nhất một thẻ, (2) trang cùng nhóm menu.
 * Nhóm menu của một trang = mục cấp 1 chứa mục đầu tiên (theo thứ tự menu) trỏ tới trang đó.
 */
final class RelatedPagesFinder
{
    public const LIMIT = 6;

    /**
     * @param list<Page>                $publicPages chỉ các trang công khai
     * @param array<int, list<string>>  $tagsByPage  id trang => thẻ
     * @param list<MenuItem>            $menuItems   mọi mục menu của hội thảo
     * @return list<Page>
     */
    public static function find(Page $current, array $publicPages, array $tagsByPage, array $menuItems): array
    {
        // Trang ứng viên: công khai, khác trang hiện tại; đánh chỉ mục theo id
        $candidates = [];
        foreach ($publicPages as $page) {
            if ($page->id !== $current->id) {
                $candidates[$page->id] = $page;
            }
        }

        // (1) Trang có chung thẻ, giữ thứ tự của danh sách trang
        $myTags = $tagsByPage[$current->id] ?? [];
        $result = [];
        foreach ($candidates as $id => $page) {
            if (array_intersect($myTags, $tagsByPage[$id] ?? []) !== []) {
                $result[$id] = $page;
            }
        }

        // (2) Trang cùng nhóm menu, theo thứ tự trong menu
        $groupOfPage = self::groupOfEachPage($menuItems);
        $myGroup = $groupOfPage[$current->id] ?? null;
        if ($myGroup !== null) {
            foreach ($groupOfPage as $pageId => $group) {
                if ($group === $myGroup && isset($candidates[$pageId])) {
                    $result[$pageId] ??= $candidates[$pageId];
                }
            }
        }

        return array_slice(array_values($result), 0, self::LIMIT);
    }

    /**
     * Nhóm menu của từng trang, theo thứ tự xuất hiện trong menu.
     *
     * @param list<MenuItem> $menuItems
     * @return array<int, int> id trang => id mục menu cấp 1
     */
    private static function groupOfEachPage(array $menuItems): array
    {
        $groupOfPage = [];
        foreach (MenuItem::inDisplayOrder($menuItems) as $item) {
            if ($item->pageId !== null && !isset($groupOfPage[$item->pageId])) {
                $groupOfPage[$item->pageId] = $item->parentId ?? $item->id;
            }
        }

        return $groupOfPage;
    }
}
