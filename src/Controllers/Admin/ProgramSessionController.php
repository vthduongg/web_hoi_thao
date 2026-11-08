<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Repositories\ProgramRepository;

/** Phiên trong một ngày. Đường dẫn: /admin/program/days/{dayId}/sessions */
final class ProgramSessionController extends CrudController
{
    protected function table(): string
    {
        return 'program_sessions';
    }

    protected function basePath(): string
    {
        return '/admin/program/days/' . $this->dayId() . '/sessions';
    }

    protected function titles(): array
    {
        return ['list' => t('program.sessions'), 'create' => t('program.session_create'), 'edit' => t('program.session_edit')];
    }

    protected function scope(): array
    {
        return ['where' => 'program_day_id = ?', 'params' => [$this->dayId()], 'insert' => ['program_day_id' => $this->dayId()]];
    }

    protected function hasVisibility(): bool
    {
        return false;
    }

    protected function fields(): array
    {
        return [
            ['name' => 'title', 'label' => t('program.session_title'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'room', 'label' => t('program.room'), 'type' => 'text', 'rules' => 'max:100', 'hint' => t('program.session_room_hint')],
        ];
    }

    protected function redirectAfterSave(): string
    {
        return '/admin/program';
    }

    /** Ngày cha — phải thuộc hội thảo đang sửa, nếu không thì 404. */
    private function dayId(): int
    {
        $dayId = (int) ($this->params['dayId'] ?? 0);
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return (new ProgramRepository($this->services->db))->dayBelongsTo($dayId, $conferenceId) ? $dayId : $this->notFound();
    }
}
