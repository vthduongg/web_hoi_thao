<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ProgramRepository;

/** Mục trong một phiên. Đường dẫn: /admin/program/sessions/{sessionId}/items */
final class ProgramItemController extends CrudController
{
    protected function table(): string
    {
        return 'program_items';
    }

    protected function basePath(): string
    {
        return '/admin/program/sessions/' . $this->sessionId() . '/items';
    }

    protected function titles(): array
    {
        return ['list' => t('program.items'), 'create' => t('program.item_create'), 'edit' => t('program.item_edit')];
    }

    protected function scope(): array
    {
        return ['where' => 'program_session_id = ?', 'params' => [$this->sessionId()], 'insert' => ['program_session_id' => $this->sessionId()]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'start_time', 'label' => t('program.start_time'), 'type' => 'time', 'rules' => 'required|time'],
            ['name' => 'end_time', 'label' => t('program.end_time'), 'type' => 'time', 'rules' => 'required|time'],
            ['name' => 'content', 'label' => t('program.content'), 'type' => 'text', 'rules' => 'required|max:1000'],
            ['name' => 'presenter', 'label' => t('program.presenter'), 'type' => 'text', 'rules' => 'max:255'],
            ['name' => 'room', 'label' => t('program.room'), 'type' => 'text', 'rules' => 'max:100', 'hint' => t('program.item_room_hint')],
        ];
    }

    protected function labelOf(array $row): string
    {
        return (string) $row['content'];
    }

    protected function formValuesFromRow(array $row): array
    {
        $values = parent::formValuesFromRow($row);
        $values['start_time'] = substr((string) $row['start_time'], 0, 5);
        $values['end_time'] = substr((string) $row['end_time'], 0, 5);

        return $values;
    }

    protected function validateExtra(array $data, ?int $id): array
    {
        $bothValid = preg_match('/^\d{2}:\d{2}$/', $data['start_time']) && preg_match('/^\d{2}:\d{2}$/', $data['end_time']);

        return $bothValid && $data['end_time'] <= $data['start_time'] ? ['end_time' => t('program.end_before_start')] : [];
    }

    protected function redirectAfterSave(): string
    {
        return '/admin/program';
    }

    /** Phiên cha — phải thuộc hội thảo đang sửa, nếu không thì 404. */
    private function sessionId(): int
    {
        $sessionId = (int) ($this->params['sessionId'] ?? 0);
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return (new ProgramRepository($this->services->db))->sessionBelongsTo($sessionId, $conferenceId) ? $sessionId : $this->notFound();
    }
}
