<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Ảnh slider trang chủ (FR-029). Mô tả ảnh bắt buộc (FR-042). */
final class SlideController extends CrudController
{
    protected function table(): string
    {
        return 'slides';
    }

    protected function basePath(): string
    {
        return '/admin/home/slides';
    }

    protected function titles(): array
    {
        return ['list' => t('slides.title'), 'create' => t('slides.create'), 'edit' => t('slides.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => t('slides.caption'), 'type' => 'text', 'rules' => 'max:255', 'hint' => t('slides.caption_hint')],
            ['name' => 'media_id', 'label' => t('slides.image'), 'type' => 'media', 'rules' => 'required'],
            ['name' => 'alt_text', 'label' => t('slides.alt_text'), 'type' => 'text', 'rules' => 'required|max:500', 'hint' => t('slides.alt_hint')],
            ['name' => 'link_url', 'label' => t('slides.link'), 'type' => 'url', 'rules' => 'url|max:1000'],
        ];
    }

    protected function labelOf(array $row): string
    {
        return (string) (($row['title'] ?? '') !== '' ? $row['title'] : $row['alt_text']);
    }

    protected function headerLinks(): array
    {
        return [[t('home.back'), '/admin/home']];
    }
}
