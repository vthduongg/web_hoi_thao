<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\PurgeRefused;
use App\Services\SlugConflict;
use App\Services\TrashService;
use InvalidArgumentException;

/** Thùng rác (FR-039): mọi người quản trị được khôi phục; chỉ quản trị viên được xoá vĩnh viễn. */
final class TrashController extends AdminController
{
    private function trash(): TrashService
    {
        return new TrashService($this->services->db, $this->services->logger, $this->services->rootDir . '/public');
    }

    public function index(): Response
    {
        $conference = $this->editingConference() ?? $this->notFound();

        return $this->render('admin/trash/index', [
            'pageTitle' => t('trash.title'),
            'items' => $this->trash()->listForConference($conference->id),
            'canPurge' => $this->currentUser()->isAdmin(),
        ]);
    }

    public function restore(string $type, string $id): Response
    {
        $newSlug = $this->request->input('slug');
        try {
            $this->trash()->restore($type, (int) $id, $this->currentUser()->id, $newSlug === '' ? null : $newSlug);
        } catch (SlugConflict) {
            return $this->render('admin/trash/restore-slug', ['pageTitle' => t('trash.restore'), 'type' => $type, 'id' => (int) $id, 'slug' => $newSlug], 409);
        } catch (InvalidArgumentException) {
            $this->notFound();
        }
        $this->services->flash->success(t('trash.restored'));

        return $this->redirect('/admin/trash');
    }

    public function purge(string $type, string $id): Response
    {
        try {
            $this->trash()->purge($type, (int) $id, $this->currentUser()->id);
            $this->services->flash->success(t('trash.purged'));
        } catch (PurgeRefused $e) {
            $this->services->flash->error($e->getMessage());
        } catch (InvalidArgumentException) {
            $this->notFound();
        }

        return $this->redirect('/admin/trash');
    }
}
