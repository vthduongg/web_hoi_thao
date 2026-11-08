<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Khối thông tin nổi bật trên trang chủ (FR-029). */
final class HighlightController extends CrudController
{
    protected function table(): string
    {
        return 'highlights';
    }

    protected function basePath(): string
    {
        return '/admin/home/highlights';
    }

    protected function titles(): array
    {
        return ['list' => t('highlights.title'), 'create' => t('highlights.create'), 'edit' => t('highlights.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => t('highlights.heading'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'body_text', 'label' => t('highlights.body'), 'type' => 'textarea', 'rules' => 'max:500'],
            ['name' => 'link_url', 'label' => t('highlights.link'), 'type' => 'url', 'rules' => 'url|max:1000'],
        ];
    }

    protected function headerLinks(): array
    {
        return [[t('home.back'), '/admin/home']];
    }
}
