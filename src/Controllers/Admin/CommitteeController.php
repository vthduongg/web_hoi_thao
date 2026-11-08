<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\CommitteeRepository;

/** Quản lý các ban / hội đồng và trang hiển thị ban (FR-031). */
final class CommitteeController extends CrudController
{
    protected function table(): string
    {
        return 'committees';
    }

    protected function basePath(): string
    {
        return '/admin/committees';
    }

    protected function titles(): array
    {
        return ['list' => t('committees.title'), 'create' => t('committees.create'), 'edit' => t('committees.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => t('committees.name'), 'type' => 'text', 'rules' => 'required|max:255', 'hint' => t('committees.name_hint')],
            ['name' => 'page_ids', 'label' => t('committees.pages'), 'type' => 'pages', 'options' => $this->committeePages(), 'empty' => t('committees.no_pages')],
        ];
    }

    protected function rowLinks(array $row): array
    {
        $count = (int) $this->services->db->value('SELECT COUNT(*) FROM committee_members WHERE committee_id = ? AND deleted_at IS NULL', [$row['id']]);

        return [
            [t('committees.members') . " ($count)", "/admin/committees/{$row['id']}/members"],
            [t('committees.import'), "/admin/committees/{$row['id']}/import"],
        ];
    }

    protected function formValuesFromRow(array $row): array
    {
        return ['name' => $row['name'], 'page_ids' => (new CommitteeRepository($this->services->db))->pageIdsFor((int) $row['id'])];
    }

    protected function afterSave(int $id, array $data): void
    {
        // Chỉ nhận các trang thuộc hội thảo đang sửa và có khối "Bảng thành viên các ban"
        $allowed = array_keys($this->committeePages());
        (new CommitteeRepository($this->services->db))->setPages($id, array_values(array_intersect($data['page_ids'], $allowed)));
    }

    /** @return array<int, string> trang có khối "committees" của hội thảo đang sửa: id => tiêu đề */
    private function committeePages(): array
    {
        $pages = [];
        foreach ($this->services->db->query(
            "SELECT id, title FROM pages WHERE conference_id = ? AND module = 'committees' AND deleted_at IS NULL ORDER BY title",
            $this->scope()['params'],
        ) as $row) {
            $pages[(int) $row['id']] = $row['title'];
        }

        return $pages;
    }
}
