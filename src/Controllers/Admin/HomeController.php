<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;

/**
 * Quản lý trang chủ (FR-029): đoạn giới thiệu; slider và khối nổi bật do
 * SlideController, HighlightController đảm nhận.
 */
final class HomeController extends AdminController
{
    public function index(): Response
    {
        $conference = $this->editingConference() ?? $this->notFound();
        $db = $this->services->db;

        return $this->render('admin/home/index', [
            'pageTitle' => t('home.title'),
            'introHtml' => (string) $conference->homeIntroHtml,
            'slideCount' => (int) $db->value('SELECT COUNT(*) FROM slides WHERE conference_id = ? AND deleted_at IS NULL', [$conference->id]),
            'highlightCount' => (int) $db->value('SELECT COUNT(*) FROM highlights WHERE conference_id = ? AND deleted_at IS NULL', [$conference->id]),
            'extraScripts' => ['vendor/tinymce/tinymce.min.js', 'js/editor.js', 'js/media-picker.js'],
        ]);
    }

    public function saveIntro(): Response
    {
        $conference = $this->editingConference() ?? $this->notFound();
        $html = $this->services->sanitizer->clean((string) ($this->request->post['home_intro_html'] ?? ''));

        $this->services->conferences->update($conference->id, ['home_intro_html' => $html]);
        $this->log('update', 'conferences', $conference->id, t('home.intro'), $conference->id);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/home');
    }
}
