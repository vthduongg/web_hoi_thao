<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Ngày trong chương trình. Đường dẫn: /admin/program/days */
final class ProgramDayController extends CrudController
{
    protected function table(): string
    {
        return 'program_days';
    }

    protected function basePath(): string
    {
        return '/admin/program/days';
    }

    protected function titles(): array
    {
        return ['list' => t('program.days'), 'create' => t('program.day_create'), 'edit' => t('program.day_edit')];
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
        return [
            ['name' => 'day_date', 'label' => t('program.day_date'), 'type' => 'date', 'rules' => 'required|date'],
            ['name' => 'title', 'label' => t('program.day_title'), 'type' => 'text', 'rules' => 'max:255', 'hint' => t('program.day_title_hint')],
        ];
    }

    protected function labelOf(array $row): string
    {
        return format_date(substr((string) $row['day_date'], 0, 10), 'admin_date') . (($row['title'] ?? '') !== '' ? ' — ' . $row['title'] : '');
    }

    protected function formValuesFromRow(array $row): array
    {
        return ['day_date' => substr((string) $row['day_date'], 0, 10), 'title' => (string) $row['title']];
    }

    protected function redirectAfterSave(): string
    {
        return '/admin/program';
    }
}
