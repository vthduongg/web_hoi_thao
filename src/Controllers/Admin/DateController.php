<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Quản lý mốc thời gian (FR-030) — nguồn ngày duy nhất của website. */
final class DateController extends CrudController
{
    protected function table(): string
    {
        return 'important_dates';
    }

    protected function basePath(): string
    {
        return '/admin/dates';
    }

    protected function titles(): array
    {
        return ['list' => t('dates.title'), 'create' => t('dates.create'), 'edit' => t('dates.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'label', 'label' => t('dates.label'), 'type' => 'text', 'rules' => 'required|max:255', 'hint' => t('dates.label_hint')],
            ['name' => 'status', 'label' => t('dates.status'), 'type' => 'select', 'rules' => 'required|in:normal,extended,tba', 'default' => 'normal', 'options' => [
                'normal' => t('dates.status_normal'), 'extended' => t('dates.status_extended'), 'tba' => t('dates.status_tba'),
            ]],
            ['name' => 'date_start', 'label' => t('dates.date_start'), 'type' => 'date', 'rules' => 'date'],
            ['name' => 'date_end', 'label' => t('dates.date_end'), 'type' => 'date', 'rules' => 'date', 'hint' => t('dates.date_end_hint')],
            ['name' => 'original_date', 'label' => t('dates.original_date'), 'type' => 'date', 'rules' => 'date', 'hint' => t('dates.original_date_hint')],
            ['name' => 'special_role', 'label' => t('dates.special_role'), 'type' => 'select', 'rules' => 'in:,abstract_deadline,full_paper_deadline,conference_day', 'options' => [
                '' => t('dates.role_none'),
                'abstract_deadline' => t('dates.role_abstract_deadline'),
                'full_paper_deadline' => t('dates.role_full_paper_deadline'),
                'conference_day' => t('dates.role_conference_day'),
            ], 'hint' => t('dates.special_role_hint')],
        ];
    }

    protected function extraCells(array $row): array
    {
        $date = $row['status'] === 'tba' ? t('dates.status_tba') : format_date(substr((string) $row['date_start'], 0, 10), 'admin_date');

        return [t('dates.date_start') => $date, t('dates.special_role') => $row['special_role'] === null ? '' : t('dates.role_' . $row['special_role'])];
    }

    protected function formValuesFromRow(array $row): array
    {
        $values = parent::formValuesFromRow($row);
        foreach (['date_start', 'date_end', 'original_date'] as $column) {
            $values[$column] = $row[$column] === null ? '' : substr((string) $row[$column], 0, 10);
        }

        return $values;
    }

    /** Quy tắc giữa các trường: ngày bắt buộc trừ khi TBA, gia hạn cần ngày cũ, kết thúc ≥ bắt đầu, vai trò không trùng. */
    protected function validateExtra(array $data, ?int $id): array
    {
        $errors = [];
        if ($data['status'] !== 'tba' && $data['date_start'] === '') {
            $errors['date_start'] = t('dates.start_required');
        }
        if ($data['status'] === 'extended' && $data['original_date'] === '') {
            $errors['original_date'] = t('dates.original_required');
        }
        if ($data['date_end'] !== '' && $data['date_start'] !== '' && $data['date_end'] < $data['date_start']) {
            $errors['date_end'] = t('dates.end_before_start');
        }
        if ($data['special_role'] !== '' && $this->roleTaken($data['special_role'], $id)) {
            $errors['special_role'] = t('dates.role_taken');
        }

        return $errors;
    }

    protected function toColumns(array $data): array
    {
        $columns = parent::toColumns($data);
        if ($data['status'] === 'tba') {
            $columns['date_start'] = $columns['date_end'] = null;
        }
        if ($data['status'] !== 'extended') {
            $columns['original_date'] = null;
        }

        return $columns;
    }

    private function roleTaken(string $role, ?int $exceptId): bool
    {
        $scope = $this->scope();

        return (int) $this->services->db->value(
            "SELECT COUNT(*) FROM important_dates WHERE {$scope['where']} AND special_role = ? AND id <> ? AND deleted_at IS NULL",
            [...$scope['params'], $role, $exceptId ?? 0],
        ) > 0;
    }
}
