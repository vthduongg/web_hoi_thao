<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Repositories\MediaRepository;
use App\Repositories\ProgramRepository;

/**
 * Trang tổng quan chương trình (ngày → phiên → mục) và tệp ảnh/PDF chương trình thay thế (FR-033).
 * Thêm/sửa từng cấp do ProgramDayController, ProgramSessionController, ProgramItemController đảm nhận.
 */
final class ProgramController extends AdminController
{
    public function index(): Response
    {
        $conference = $this->editingConference() ?? $this->notFound();
        $media = new MediaRepository($this->services->db);

        return $this->render('admin/program/index', [
            'pageTitle' => t('program.title'),
            'days' => (new ProgramRepository($this->services->db))->tree($conference->id),
            'programFile' => $media->find($conference->programFileMediaId),
            'extraScripts' => ['js/media-picker.js'],
        ]);
    }

    /** Chọn (hoặc bỏ) tệp ảnh/PDF chương trình thay thế. */
    public function saveFile(): Response
    {
        $conference = $this->editingConference() ?? $this->notFound();
        $input = $this->request->input('program_file_media_id');
        $mediaId = ctype_digit($input) && (new MediaRepository($this->services->db))->find((int) $input) !== null ? (int) $input : null;

        $this->services->conferences->update($conference->id, ['program_file_media_id' => $mediaId]);
        $this->log('update', 'conferences', $conference->id, t('program.file'), $conference->id);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/program');
    }
}
