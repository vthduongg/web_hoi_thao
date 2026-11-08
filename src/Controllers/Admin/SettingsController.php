<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\Conference;
use App\Repositories\MediaRepository;

/**
 * Thông tin chung của hội thảo (FR-022), chỉ quản trị viên. Mọi trang công khai lấy thông tin từ đây.
 * Ngày tổ chức được lưu vào mốc thời gian có vai trò "conference_day" (một nguồn ngày duy nhất — FR-002).
 */
final class SettingsController extends AdminController
{
    public function edit(): Response
    {
        $conference = $this->conference();

        return $this->form($conference, $this->valuesFrom($conference));
    }

    public function update(): Response
    {
        $conference = $this->conference();
        $data = [];
        foreach ($this->fields() as $field) {
            $data[$field['name']] = $this->request->input($field['name']);
        }
        $errors = $this->validateSettings($data, $conference);
        if ($errors !== []) {
            return $this->form($conference, $data, $errors);
        }

        $this->services->db->transaction(function () use ($conference, $data): void {
            $this->services->conferences->update($conference->id, $this->conferenceColumns($data));
            $this->saveConferenceDays($conference->id, $data['conference_date_start'], $data['conference_date_end']);
        });
        $this->log('update', 'conferences', $conference->id, $data['name'], $conference->id);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/settings');
    }

    /** @return list<array<string, mixed>> trường của biểu mẫu (vẽ bằng templates/admin/crud/field.php) */
    private function fields(): array
    {
        $url = static fn (string $name, string $label): array => ['name' => $name, 'label' => $label, 'type' => 'url', 'rules' => 'url|max:1000'];

        return [
            ['name' => 'name', 'label' => t('settings.name'), 'type' => 'text', 'rules' => 'required|max:255'],
            ['name' => 'theme', 'label' => t('settings.theme'), 'type' => 'text', 'rules' => 'max:255'],
            ['name' => 'year', 'label' => t('settings.year'), 'type' => 'text', 'rules' => 'required|integer', 'hint' => t('settings.year_hint')],
            ['name' => 'conference_date_start', 'label' => t('settings.date_start'), 'type' => 'date', 'rules' => 'date'],
            ['name' => 'conference_date_end', 'label' => t('settings.date_end'), 'type' => 'date', 'rules' => 'date', 'hint' => t('settings.date_hint')],
            ['name' => 'venue', 'label' => t('settings.venue'), 'type' => 'text', 'rules' => 'max:500'],
            ['name' => 'conference_language', 'label' => t('settings.language'), 'type' => 'text', 'rules' => 'max:100', 'hint' => t('settings.language_hint')],
            ['name' => 'logo_media_id', 'label' => t('settings.logo'), 'type' => 'media'],
            ['name' => 'favicon_media_id', 'label' => t('settings.favicon'), 'type' => 'media'],
            ['name' => 'contact_email', 'label' => t('settings.email'), 'type' => 'email', 'rules' => 'email|max:255'],
            ['name' => 'contact_phone', 'label' => t('settings.phone'), 'type' => 'tel', 'rules' => 'phone|max:50'],
            ['name' => 'footer_text', 'label' => t('settings.footer'), 'type' => 'textarea', 'rules' => 'max:1000'],
            ['name' => 'copyright_text', 'label' => t('settings.copyright'), 'type' => 'text', 'rules' => 'max:255'],
            $url('submission_url', t('settings.submission_url')) + ['hint' => t('settings.submission_hint')],
            $url('facebook_url', 'Facebook'),
            $url('linkedin_url', 'LinkedIn'),
            $url('x_url', 'X (Twitter)'),
            $url('youtube_url', 'YouTube'),
        ];
    }

    private function validateSettings(array $data, Conference $conference): array
    {
        $rules = array_column($this->fields(), 'rules', 'name');
        $errors = $this->validate($data, array_filter($rules));
        $yearTaken = (int) $this->services->db->value('SELECT COUNT(*) FROM conferences WHERE year = ? AND id <> ?', [(int) $data['year'], $conference->id]) > 0;
        if (!isset($errors['year']) && $yearTaken) {
            $errors['year'] = t('settings.year_taken');
        }
        if ($data['conference_date_end'] !== '' && $data['conference_date_start'] !== '' && $data['conference_date_end'] < $data['conference_date_start']) {
            $errors['conference_date_end'] = t('dates.end_before_start');
        }

        return $errors;
    }

    /** @return array<string, mixed> cột của bảng conferences (chuỗi rỗng → NULL) */
    private function conferenceColumns(array $data): array
    {
        $columns = [];
        foreach ($this->fields() as $field) {
            $name = $field['name'];
            if (!str_starts_with($name, 'conference_date_')) {
                $columns[$name] = $data[$name] === '' ? null : $data[$name];
            }
        }
        $columns['year'] = (int) $data['year'];
        foreach (['logo_media_id', 'favicon_media_id'] as $media) {
            $columns[$media] = ctype_digit((string) $data[$media]) ? (int) $data[$media] : null;
        }

        return $columns;
    }

    /** Ghi ngày tổ chức vào mốc "conference_day" (tạo mốc nếu chưa có; để trống thì không đổi). */
    private function saveConferenceDays(int $conferenceId, string $start, string $end): void
    {
        if ($start === '') {
            return;
        }
        $db = $this->services->db;
        $existing = $db->value("SELECT id FROM important_dates WHERE conference_id = ? AND special_role = 'conference_day' AND deleted_at IS NULL", [$conferenceId]);
        $values = ['date_start' => $start, 'date_end' => $end === '' ? null : $end, 'status' => 'normal', 'original_date' => null];
        if ($existing !== null) {
            $db->update('important_dates', $values, 'id = ?', [$existing]);

            return;
        }
        $next = (int) $db->value('SELECT ISNULL(MAX(sort_order), 0) + 10 FROM important_dates WHERE conference_id = ?', [$conferenceId]);
        $db->insert('important_dates', $values + ['conference_id' => $conferenceId, 'label' => 'Conference days', 'special_role' => 'conference_day', 'sort_order' => $next]);
    }

    /** @return array<string, mixed> giá trị ban đầu của biểu mẫu */
    private function valuesFrom(Conference $conference): array
    {
        $row = $this->services->db->first('SELECT * FROM conferences WHERE id = ?', [$conference->id]);
        $day = $this->services->db->first("SELECT date_start, date_end FROM important_dates WHERE conference_id = ? AND special_role = 'conference_day' AND deleted_at IS NULL", [$conference->id]);
        $values = [];
        foreach ($this->fields() as $field) {
            $values[$field['name']] = (string) ($row[$field['name']] ?? '');
        }
        $values['conference_date_start'] = $day === null || $day['date_start'] === null ? '' : substr((string) $day['date_start'], 0, 10);
        $values['conference_date_end'] = $day === null || $day['date_end'] === null ? '' : substr((string) $day['date_end'], 0, 10);

        return $values;
    }

    private function form(Conference $conference, array $values, array $errors = []): Response
    {
        $media = new MediaRepository($this->services->db);

        return $this->render('admin/crud/form', [
            'pageTitle' => t('settings.title'),
            'action' => '/admin/settings',
            'cancelUrl' => '/admin',
            'fields' => $this->fields(),
            'values' => $values,
            'errors' => $errors,
            'mediaPreviews' => [
                'logo_media_id' => $media->find(ctype_digit((string) $values['logo_media_id']) ? (int) $values['logo_media_id'] : null),
                'favicon_media_id' => $media->find(ctype_digit((string) $values['favicon_media_id']) ? (int) $values['favicon_media_id'] : null),
            ],
            'extraScripts' => ['js/media-picker.js'],
        ], $errors === [] ? 200 : 422);
    }

    private function conference(): Conference
    {
        return $this->editingConference() ?? $this->notFound();
    }
}
