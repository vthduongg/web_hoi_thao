<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Repositories\MediaRepository;
use App\Services\MediaUploader;
use App\Services\UploadRejected;

/** Thư viện tệp: hộp chọn tệp (dùng trong trình soạn thảo và các ô ảnh) và tải lên. */
final class MediaController extends AdminController
{
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
