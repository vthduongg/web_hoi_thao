<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\SponsorRepository;

/** Quản lý nhà tài trợ / đối tác (FR-034). */
final class SponsorController extends CrudController
{
    protected function table(): string
    {
        return 'sponsors';
    }

    protected function basePath(): string
    {
        return '/admin/sponsors';
    }

    protected function titles(): array
    {
        return ['list' => t('sponsors.title'), 'create' => t('sponsors.create'), 'edit' => t('sponsors.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        $tiers = $this->tiers();

        return [
            ['name' => 'name', 'label' => t('sponsors.name'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'sponsor_tier_id', 'label' => t('sponsors.tier'), 'type' => 'select', 'options' => ['' => t('sponsors.choose_tier')] + $tiers,
                'rules' => 'required|in:' . implode(',', array_keys($tiers)), 'hint' => t('sponsors.tier_hint')],
            ['name' => 'logo_media_id', 'label' => t('sponsors.logo'), 'type' => 'media', 'rules' => 'required'],
            ['name' => 'website_url', 'label' => t('sponsors.website'), 'type' => 'url', 'rules' => 'url|max:1000'],
        ];
    }

    protected function extraCells(array $row): array
    {
        return [t('sponsors.tier') => $this->tiers()[(int) $row['sponsor_tier_id']] ?? ''];
    }

    protected function headerLinks(): array
    {
        return [[t('tiers.title'), '/admin/sponsor-tiers']];
    }

    /** @return array<int, string> */
    private function tiers(): array
    {
        return (new SponsorRepository($this->services->db))->tierOptions($this->scope()['params'][0]);
    }
}
