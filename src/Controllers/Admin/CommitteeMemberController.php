<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Quản lý thành viên của một ban (FR-031). Đường dẫn: /admin/committees/{committeeId}/members */
final class CommitteeMemberController extends CrudController
{
    protected function table(): string
    {
        return 'committee_members';
    }

    protected function basePath(): string
    {
        return '/admin/committees/' . $this->committee()['id'] . '/members';
    }

    protected function titles(): array
    {
        $name = $this->committee()['name'];

        return [
            'list' => t('members.title', ['committee' => $name]),
            'create' => t('members.create', ['committee' => $name]),
            'edit' => t('members.edit', ['committee' => $name]),
        ];
    }

    protected function scope(): array
    {
        $committeeId = (int) $this->committee()['id'];

        return ['where' => 'committee_id = ?', 'params' => [$committeeId], 'insert' => ['committee_id' => $committeeId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'full_name', 'label' => t('members.full_name'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'academic_title', 'label' => t('members.academic_title'), 'type' => 'text', 'rules' => 'max:100', 'hint' => t('members.academic_title_hint')],
            ['name' => 'position_affiliation', 'label' => t('members.position_affiliation'), 'type' => 'text', 'rules' => 'max:500'],
            ['name' => 'committee_role', 'label' => t('members.role'), 'type' => 'text', 'rules' => 'max:100', 'hint' => t('members.role_hint')],
            ['name' => 'photo_media_id', 'label' => t('members.photo'), 'type' => 'media'],
        ];
    }

    protected function extraCells(array $row): array
    {
        return [t('members.role') => (string) $row['committee_role'], t('members.position_affiliation') => (string) $row['position_affiliation']];
    }

    protected function headerLinks(): array
    {
        return [
            [t('committees.import'), '/admin/committees/' . $this->committee()['id'] . '/import'],
            [t('committees.back_to_list'), '/admin/committees'],
        ];
    }

    /** Ban đang xem — phải thuộc hội thảo đang sửa, nếu không thì 404. */
    private function committee(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return $this->services->db->first(
            'SELECT id, name FROM committees WHERE id = ? AND conference_id = ? AND deleted_at IS NULL',
            [(int) ($this->params['committeeId'] ?? 0), $conferenceId],
        ) ?? $this->notFound();
    }
}
