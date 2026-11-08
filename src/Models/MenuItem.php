<?php

declare(strict_types=1);

namespace App\Models;

/** Một mục menu (bảng menu_items). Trỏ tới một trang trong site, một link ngoài, hoặc chỉ là mục cha. */
final class MenuItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $conferenceId,
        public readonly ?int $parentId,
        public readonly string $label,
        public readonly ?int $pageId,
        public readonly ?string $externalUrl,
        public readonly int $sortOrder,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            conferenceId: (int) $row['conference_id'],
            parentId: $row['parent_id'],
            label: $row['label'],
            pageId: $row['page_id'],
            externalUrl: $row['external_url'],
            sortOrder: (int) $row['sort_order'],
        );
    }

    /**
     * Sắp các mục theo thứ tự hiển thị: mục cấp 1 theo sort_order, ngay sau mỗi mục là các mục con của nó.
     *
     * @param list<MenuItem> $items
     * @return list<MenuItem>
     */
    public static function inDisplayOrder(array $items): array
    {
        usort($items, static fn (MenuItem $a, MenuItem $b): int => [$a->sortOrder, $a->id] <=> [$b->sortOrder, $b->id]);

        $ordered = [];
        foreach ($items as $top) {
            if ($top->parentId !== null) {
                continue;
            }
            $ordered[] = $top;
            foreach ($items as $child) {
                if ($child->parentId === $top->id) {
                    $ordered[] = $child;
                }
            }
        }

        return $ordered;
    }
}
