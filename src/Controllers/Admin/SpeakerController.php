<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Quản lý diễn giả chính (FR-032). */
final class SpeakerController extends CrudController
{
    protected function table(): string
    {
        return 'keynote_speakers';
    }

    protected function basePath(): string
    {
        return '/admin/speakers';
    }

    protected function titles(): array
    {
        return ['list' => t('speakers.title'), 'create' => t('speakers.create'), 'edit' => t('speakers.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'full_name', 'label' => t('speakers.full_name'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'academic_title', 'label' => t('speakers.academic_title'), 'type' => 'text', 'rules' => 'max:100'],
            ['name' => 'affiliation', 'label' => t('speakers.affiliation'), 'type' => 'text', 'rules' => 'max:500'],
            ['name' => 'country', 'label' => t('speakers.country'), 'type' => 'text', 'rules' => 'max:100'],
            ['name' => 'talk_title', 'label' => t('speakers.talk_title'), 'type' => 'text', 'rules' => 'max:500'],
            ['name' => 'photo_media_id', 'label' => t('speakers.photo'), 'type' => 'media'],
            ['name' => 'bio_html', 'label' => t('speakers.bio'), 'type' => 'rich'],
        ];
    }

    protected function extraCells(array $row): array
    {
        return [t('speakers.affiliation') => (string) $row['affiliation'], t('speakers.country') => (string) $row['country']];
    }

    /** Tiểu sử soạn bằng trình soạn thảo nên phải lọc HTML trước khi lưu. */
    protected function toColumns(array $data): array
    {
        $columns = parent::toColumns($data);
        $columns['bio_html'] = $this->services->sanitizer->clean($data['bio_html']);

        return $columns;
    }
}
