<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Quản lý hạng nhà tài trợ: Đơn vị tổ chức, Phối hợp, Tài trợ... (FR-034). */
final class SponsorTierController extends CrudController
{
    protected function table(): string
    {
        return 'sponsor_tiers';
    }

    protected function basePath(): string
    {
        return '/admin/sponsor-tiers';
    }

    protected function titles(): array
    {
        return ['list' => t('tiers.title'), 'create' => t('tiers.create'), 'edit' => t('tiers.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function hasVisibility(): bool
    {
        return false;
    }

    protected function fields(): array
    {
        return [['name' => 'name', 'label' => t('tiers.name'), 'type' => 'text', 'rules' => 'required|max:100', 'hint' => t('tiers.name_hint')]];
    }

    protected function headerLinks(): array
    {
        return [[t('tiers.back_to_sponsors'), '/admin/sponsors']];
    }
}
