<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\CommitteeImporter;

/** Nhập thành viên ban từ tệp Excel/CSV (FR-031). Đường dẫn: /admin/committees/{committeeId}/import */
final class CommitteeImportController extends AdminController
{
    public function show(string $committeeId): Response
    {
        return $this->page($this->committee($committeeId), []);
    }

    public function import(string $committeeId): Response
    {
        $committee = $this->committee($committeeId);
        $file = $this->request->file('file');
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return $this->page($committee, [t('import.no_file')]);
        }

        $errors = (new CommitteeImporter($this->services->db))->import((int) $committee['id'], $file['tmp_name'], $file['name']);
        if ($errors !== []) {
            return $this->page($committee, $errors);
        }

        $this->log('import', 'committees', (int) $committee['id'], $committee['name'], $this->editingConference()?->id);
        $this->services->flash->success(t('import.done'));

        return $this->redirect("/admin/committees/{$committee['id']}/members");
    }

    /** @param list<string> $errors */
    private function page(array $committee, array $errors): Response
    {
        return $this->render('admin/committees/import', [
            'pageTitle' => t('import.title', ['committee' => $committee['name']]),
            'committee' => $committee,
            'importErrors' => $errors,
        ], $errors === [] ? 200 : 422);
    }

    /** Ban thuộc hội thảo đang sửa, hoặc 404. */
    private function committee(string $id): array
    {
        $conferenceId = ($this->editingConference() ?? $this->notFound())->id;

        return $this->services->db->first(
            'SELECT id, name FROM committees WHERE id = ? AND conference_id = ? AND deleted_at IS NULL',
            [(int) $id, $conferenceId],
        ) ?? $this->notFound();
    }
}
