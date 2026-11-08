<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\MenuItem;
use App\Repositories\MenuRepository;
use InvalidArgumentException;

/**
 * Quản lý menu website (FR-028), chỉ quản trị viên. Thêm/sửa/xoá dùng CrudController;
 * danh sách hiển thị dạng cây 2 cấp, kéo thả hoặc ▲/▼/→/← để sắp xếp và đổi cấp.
 */
final class MenuController extends CrudController
{
    protected function table(): string
    {
        return 'menu_items';
    }

    protected function basePath(): string
    {
        return '/admin/menu';
    }

    protected function titles(): array
    {
        return ['list' => t('menu.title'), 'create' => t('menu.create'), 'edit' => t('menu.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = $this->conferenceId();

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function hasVisibility(): bool
    {
        return false;
    }

    protected function fields(): array
    {
        $pages = ['' => t('menu.no_page')] + array_column($this->pages(), 'title', 'id');

        return [
            ['name' => 'label', 'label' => t('menu.label'), 'type' => 'text', 'rules' => 'required|max:100'],
            ['name' => 'page_id', 'label' => t('menu.page'), 'type' => 'select', 'options' => $pages, 'rules' => 'in:' . implode(',', array_keys($pages))],
            ['name' => 'external_url', 'label' => t('menu.external_url'), 'type' => 'url', 'rules' => 'url|max:1000', 'hint' => t('menu.target_hint')],
        ];
    }

    protected function validateExtra(array $data, ?int $id): array
    {
        return $data['page_id'] !== '' && $data['external_url'] !== '' ? ['external_url' => t('menu.both_targets')] : [];
    }

    /** Cây menu 2 cấp thay cho danh sách phẳng. */
    public function index(string ...$route): Response
    {
        $items = (new MenuRepository($this->services->db))->allForConference($this->conferenceId());
        $slugs = array_column($this->pages(), 'slug', 'id');
        $describe = fn (MenuItem $item): string => $item->externalUrl ?? ($item->pageId !== null ? '/' . ($slugs[$item->pageId] ?? '?') : t('menu.group_only'));

        $tree = [];
        foreach ($items as $item) {
            if ($item->parentId === null) {
                $children = array_values(array_filter($items, static fn (MenuItem $child): bool => $child->parentId === $item->id));
                $tree[] = ['item' => $item, 'target' => $describe($item), 'children' => array_map(
                    static fn (MenuItem $child): array => ['item' => $child, 'target' => $describe($child)],
                    $children,
                )];
            }
        }

        return $this->render('admin/menu/index', ['pageTitle' => t('menu.title'), 'tree' => $tree, 'extraScripts' => ['js/sortable-list.js']]);
    }

    /** Nhận cây mới: items[i][id], items[i][parent_id]. */
    public function reorder(string ...$route): Response
    {
        try {
            (new MenuRepository($this->services->db))->saveTree($this->conferenceId(), $this->request->inputArray('items'));
        } catch (InvalidArgumentException $e) {
            return Response::json(['error' => $e->getMessage() === 'too_deep' ? t('menu.too_deep') : t('crud.reorder_invalid')], 422);
        }
        $this->log('update', 'menu_items', null, t('menu.title'), $this->conferenceId());

        return Response::json(['ok' => true]);
    }

    private function conferenceId(): int
    {
        return ($this->editingConference() ?? $this->notFound())->id;
    }

    /** @return list<array{id: int, title: string, slug: string}> trang chưa xoá của hội thảo (đích của mục menu) */
    private function pages(): array
    {
        return $this->services->db->query('SELECT id, title, slug FROM pages WHERE conference_id = ? AND deleted_at IS NULL ORDER BY title', [$this->conferenceId()]);
    }
}
