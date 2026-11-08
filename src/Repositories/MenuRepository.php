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
     * Lưu thứ tự và cấp của toàn bộ menu (từ sortable-list.js), trong một transaction.
     *
     * @param list<array{id: string|int, parent_id: string|int|null}> $items theo thứ tự hiển thị
     * @throws \InvalidArgumentException khi có id lạ, cha không hợp lệ, hoặc vượt quá 2 cấp
     */
    public function saveTree(int $conferenceId, array $items): void
    {
        $allowed = array_map(static fn (MenuItem $item): int => $item->id, $this->allForConference($conferenceId));
        $parentOf = self::validateTree($items, $allowed);

        $this->db->transaction(function () use ($parentOf): void {
            $position = 0;
            foreach ($parentOf as $id => $parentId) {
                $this->db->update('menu_items', ['parent_id' => $parentId, 'sort_order' => ++$position * 10], 'id = ?', [$id]);
            }
        });
    }

    /**
     * Kiểm tra cây menu gửi lên: id thuộc hội thảo, không trùng, cha là mục cấp 1 đứng trước, tối đa 2 cấp.
     *
     * @param list<int> $allowedIds
     * @return array<int, ?int> id => id cha (theo đúng thứ tự gửi lên)
     */
    public static function validateTree(array $items, array $allowedIds): array
    {
        $parentOf = [];
        foreach ($items as $item) {
            $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $rawParent = $item['parent_id'] ?? '';
            $parentId = $rawParent === '' || $rawParent === null ? null : filter_var($rawParent, FILTER_VALIDATE_INT);
            if ($id === false || $parentId === false || !in_array($id, $allowedIds, true) || isset($parentOf[$id])) {
                throw new \InvalidArgumentException('invalid');
            }
            if ($parentId !== null && !array_key_exists($parentId, $parentOf)) {
                throw new \InvalidArgumentException('invalid');
            }
            if ($parentId !== null && $parentOf[$parentId] !== null) {
                throw new \InvalidArgumentException('too_deep');
            }
            $parentOf[$id] = $parentId;
        }

        return $parentOf;
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
