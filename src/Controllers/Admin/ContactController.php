<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

/** Quản lý người liên hệ (FR-035). */
final class ContactController extends CrudController
{
    protected function table(): string
    {
        return 'contact_persons';
    }

    protected function basePath(): string
    {
        return '/admin/contacts';
    }

    protected function titles(): array
    {
        return ['list' => t('contacts.title'), 'create' => t('contacts.create'), 'edit' => t('contacts.edit')];
    }

    protected function scope(): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return ['where' => 'conference_id = ?', 'params' => [$conferenceId], 'insert' => ['conference_id' => $conferenceId]];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'full_name', 'label' => t('contacts.full_name'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'affiliation', 'label' => t('contacts.affiliation'), 'type' => 'text', 'rules' => 'max:500'],
            ['name' => 'email', 'label' => t('contacts.email'), 'type' => 'email', 'rules' => 'email|max:255'],
            ['name' => 'phone', 'label' => t('contacts.phone'), 'type' => 'tel', 'rules' => 'phone|max:50', 'hint' => t('contacts.phone_hint')],
        ];
    }

    protected function extraCells(array $row): array
    {
        return [t('contacts.email') => (string) $row['email'], t('contacts.phone') => (string) $row['phone']];
    }
}
