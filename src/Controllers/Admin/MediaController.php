<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Clock;
use App\Core\Response;
use App\Models\MediaFile;
use App\Repositories\MediaRepository;
use App\Services\MediaUploader;
use App\Services\MediaUsageFinder;
use App\Services\UploadRejected;

/** Thư viện tệp (FR-036): danh sách, tải lên, sửa mô tả ảnh, xoá có cảnh báo nơi dùng, hộp chọn tệp. */
final class MediaController extends AdminController
{
    public function index(): Response
    {
        $query = $this->request->input('q');
        $kind = $this->request->input('kind');

        return $this->render('admin/media/index', [
            'pageTitle' => t('media.title'),
            'items' => (new MediaRepository($this->services->db))->search($query, $kind === '' ? null : $kind),
            'query' => $query,
            'kind' => $kind,
        ]);
    }

    public function edit(string $id): Response
    {
        return $this->editForm($this->findMedia($id), []);
    }

    /** Sửa mô tả ảnh (dùng làm alt mặc định khi chèn ảnh). */
    public function update(string $id): Response
    {
        $media = $this->findMedia($id);
        $altText = $this->request->input('alt_text');
        $errors = $this->validate(['alt_text' => $altText], ['alt_text' => 'max:500']);
        if ($errors !== []) {
            return $this->editForm($media, $errors, 422);
        }

        $this->services->db->update('media_files', ['alt_text' => $altText === '' ? null : $altText], 'id = ?', [$media->id]);
        $this->log('update', 'media_files', $media->id, $media->originalName);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/media');
    }

    /** Tệp đang được dùng: hiện danh sách nơi dùng và hỏi xác nhận lần hai; sau đó chuyển vào thùng rác. */
    public function delete(string $id): Response
    {
        $media = $this->findMedia($id);
        $usages = (new MediaUsageFinder($this->services->db))->find($media->id);
        if ($usages !== [] && $this->request->input('confirmed') !== '1') {
            return $this->render('admin/media/confirm-delete', ['pageTitle' => t('common.delete'), 'media' => $media, 'usages' => $usages]);
        }

        $this->services->db->update('media_files', ['deleted_at' => Clock::nowForDatabase(), 'deleted_by' => $this->currentUser()->id], 'id = ?', [$media->id]);
        $this->log('delete', 'media_files', $media->id, $media->originalName);
        $this->services->flash->success(t('common.deleted'));

        return $this->redirect('/admin/media');
    }

    private function findMedia(string $id): MediaFile
    {
        return (new MediaRepository($this->services->db))->find((int) $id) ?? $this->notFound();
    }

    private function editForm(MediaFile $media, array $errors, int $status = 200): Response
    {
        return $this->render('admin/media/form', ['pageTitle' => t('media.edit'), 'media' => $media, 'errors' => $errors], $status);
    }

    /** Hộp chọn tệp — mở trong cửa sổ riêng, chọn xong gửi kết quả về cửa sổ gọi (media-picker.js). */
    public function picker(): Response
    {
        $query = $this->request->input('q');
        $kind = $this->request->input('kind');

        $html = $this->services->view->render('admin/media/picker', [
            'items' => (new MediaRepository($this->services->db))->search($query, $kind === '' ? null : $kind),
            'query' => $query,
            'kind' => $kind,
            'csrfToken' => $this->services->csrf->token(),
            'flashMessages' => $this->services->flash->take(),
        ]);

        return Response::html($html);
    }

    /** Nhận một hoặc nhiều tệp (trường files[]). */
    public function upload(): Response
    {
        $uploader = new MediaUploader($this->services->db, $this->services->rootDir . '/public');
        $uploaded = 0;
        foreach (self::normalizeFiles($this->request->files['files'] ?? []) as $file) {
            try {
                $uploader->upload($file, $this->currentUser()->id);
                $uploaded++;
            } catch (UploadRejected $e) {
                $this->services->flash->error($e->getMessage());
            }
        }
        if ($uploaded > 0) {
            $this->services->flash->success(t('media.uploaded', ['count' => $uploaded]));
        }

        // Tải lên từ màn hình Thư viện tệp thì quay về thư viện; từ hộp chọn tệp thì quay về hộp chọn
        if ($this->request->input('return') === 'library') {
            return $this->redirect('/admin/media');
        }
        $kind = $this->request->input('kind');

        return $this->redirect('/admin/media/picker' . ($kind !== '' ? '?kind=' . rawurlencode($kind) : ''));
    }

    /**
     * $_FILES của ô chọn nhiều tệp có dạng ['name' => [...], 'tmp_name' => [...]] — đổi thành danh sách từng tệp.
     *
     * @return list<array{name: string, tmp_name: string, size: int, error: int}>
     */
    private static function normalizeFiles(array $files): array
    {
        if (!isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }

        $list = [];
        foreach (array_keys($files['name']) as $i) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $list[] = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => (int) $files['size'][$i],
                'error' => (int) $files['error'][$i],
            ];
        }

        return $list;
    }
}
