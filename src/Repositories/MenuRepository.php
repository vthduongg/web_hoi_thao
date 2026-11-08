<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\MenuItem;

/** Truy vấn bảng menu_items. */
final class MenuRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<MenuItem> mọi mục chưa xoá của hội thảo, theo thứ tự hiển thị */
    public function allForConference(int $conferenceId): array
    {
        return MenuItem::inDisplayOrder(array_map([MenuItem::class, 'fromRow'], $this->db->query(
            'SELECT * FROM menu_items WHERE conference_id = ? AND deleted_at IS NULL',
            [$conferenceId],
        )));
    }

    /**
     * Cây menu để hiển thị công khai (tối đa 2 cấp).
     *
     * Mục trỏ tới trang không công khai bị ẩn; mục cha không có link và không còn mục con nào cũng bị ẩn.
     *
     * @param array<int, string> $publicSlugById id trang công khai => slug
     * @param callable(string): string $urlFor tạo đường dẫn từ "/slug"
     * @return list<array{label: string, href: ?string, external: bool, children: list<array>}>
     */
    public function treeForPublic(int $conferenceId, array $publicSlugById, callable $urlFor): array
    {
        $items = $this->allForConference($conferenceId);
        $link = static function (MenuItem $item) use ($publicSlugById, $urlFor): ?array {
            if ($item->externalUrl !== null) {
                return ['href' => $item->externalUrl, 'external' => true];
            }
            if ($item->pageId !== null) {
                $slug = $publicSlugById[$item->pageId] ?? null;

                return $slug === null ? null : ['href' => $urlFor('/' . $slug), 'external' => false];
            }

            return ['href' => null, 'external' => false];
        };

        $tree = [];
        foreach ($items as $top) {
            if ($top->parentId !== null) {
                continue;
            }
            $children = [];
            foreach ($items as $child) {
                $childLink = $child->parentId === $top->id ? $link($child) : null;
                if ($childLink !== null && $childLink['href'] !== null) {
                    $children[] = ['label' => $child->label] + $childLink + ['children' => []];
                }
            }
            $topLink = $link($top);
            if ($topLink === null || ($topLink['href'] === null && $children === [])) {
                continue;
            }
            $tree[] = ['label' => $top->label] + $topLink + ['children' => $children];
        }

        return $tree;
    }
}
