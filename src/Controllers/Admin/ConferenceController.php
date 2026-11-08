<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\ConferenceCopier;
use InvalidArgumentException;

/**
 * Các kỳ hội thảo (FR-038): sao chép sang năm mới, đặt hiện hành (chỉ quản trị viên);
 * chọn hội thảo đang sửa (mọi người quản trị).
 */
final class ConferenceController extends AdminController
{
    public function index(): Response
    {
        return $this->page([]);
    }

    public function copy(string $id): Response
    {
        $source = $this->services->conferences->find((int) $id) ?? $this->notFound();
        $year = $this->request->input('year');
        $errors = $this->validate(['year' => $year], ['year' => 'required|integer']);
        if ($errors === []) {
            try {
                $newId = (new ConferenceCopier($this->services->db, $this->services->logger))->copy($source->id, (int) $year, $this->currentUser()->id);
                $this->setEditingConference($newId);
                $this->services->flash->success(t('conferences.copied', ['year' => $year]));

                return $this->redirect('/admin/conferences');
            } catch (InvalidArgumentException $e) {
                $errors['year'] = $e->getMessage();
            }
        }

        return $this->page($errors, $source->id, 422);
    }

    public function makeCurrent(string $id): Response
    {
        $conference = $this->services->conferences->find((int) $id) ?? $this->notFound();
        $this->services->conferences->makeCurrent($conference->id);
        $this->log('update', 'conferences', $conference->id, t('conferences.made_current_log', ['name' => $conference->name]), $conference->id);
        $this->services->flash->success(t('conferences.made_current', ['name' => $conference->name]));

        return $this->redirect('/admin/conferences');
    }

    /** Chọn hội thảo để sửa (lưu trong phiên đăng nhập). */
    public function switch(): Response
    {
        $conference = $this->services->conferences->find((int) $this->request->input('conference_id')) ?? $this->notFound();
        $this->setEditingConference($conference->id);

        return $this->redirect('/admin');
    }

    private function page(array $errors, ?int $copySourceId = null, int $status = 200): Response
    {
        return $this->render('admin/conferences/index', [
            'pageTitle' => t('nav.conferences'),
            'conferences' => $this->services->conferences->all(),
            'errors' => $errors,
            'copySourceId' => $copySourceId,
        ], $status);
    }
}
