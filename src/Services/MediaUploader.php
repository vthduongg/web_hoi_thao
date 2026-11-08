<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use App\Models\MediaFile;
use App\Repositories\MediaRepository;
use ZipArchive;

/**
 * Nhận tệp tải lên vào thư viện (FR-036).
 *
 * - Loại tệp xác định bằng nội dung (finfo), không tin đuôi tệp. KHÔNG nhận SVG (research.md R6).
 * - Ảnh tối đa 5 MB, tài liệu tối đa 20 MB.
 * - Lưu với tên ngẫu nhiên trong public/uploads/năm/tháng/.
 * - Ảnh (trừ GIF động) được tạo thêm bản thu nhỏ rộng 400 px và 1200 px.
 */
final class MediaUploader
{
    private const MB = 1024 * 1024;
    public const MAX_IMAGE_BYTES = 5 * self::MB;
    public const MAX_DOCUMENT_BYTES = 20 * self::MB;
    public const THUMB_SMALL_WIDTH = 400;
    public const THUMB_LARGE_WIDTH = 1200;

    /** Loại MIME được nhận => [đuôi tệp lưu, nhóm] */
    private const ALLOWED = [
        'image/jpeg' => ['jpg', 'image'],
        'image/png' => ['png', 'image'],
        'image/webp' => ['webp', 'image'],
        'image/gif' => ['gif', 'image'],
        'application/pdf' => ['pdf', 'document'],
        'application/msword' => ['doc', 'document'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx', 'document'],
    ];

    private readonly MediaRepository $media;

    /** @param string $publicDir thư mục public/ (tệp lưu vào $publicDir/uploads/...) */
    public function __construct(Database $db, private readonly string $publicDir)
    {
        $this->media = new MediaRepository($db);
    }

    /**
     * @param array{name: string, tmp_name: string, size: int, error: int} $file một phần tử của $_FILES
     * @throws UploadRejected
     */
    public function upload(array $file, ?int $userId, ?string $altText = null): MediaFile
    {
        [$mime, $extension, $kind] = $this->checkTypeAndSize($file);

        $storedPath = 'uploads/' . Clock::now()->format('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        $this->storeFile($file['tmp_name'], $storedPath);
        $image = $kind === 'image' ? $this->imageDetails($storedPath, $mime) : ['width' => null, 'height' => null, 'thumb_small_path' => null, 'thumb_large_path' => null];

        $id = $this->media->create([
            'original_name' => mb_substr($file['name'], 0, 255),
            'stored_path' => $storedPath,
            'mime_type' => $mime,
            'size_bytes' => (int) filesize($this->absolute($storedPath)),
            'kind' => $kind,
            'alt_text' => $altText,
            'uploaded_by' => $userId,
        ] + $image);

        return $this->media->find($id);
    }

    /**
     * Kiểm tra lỗi tải lên, loại tệp và dung lượng.
     *
     * @return array{0: string, 1: string, 2: string} [loại MIME, đuôi tệp lưu, nhóm image|document]
     * @throws UploadRejected
     */
    private function checkTypeAndSize(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_file($file['tmp_name'])) {
            throw new UploadRejected(t('media.upload_failed', ['name' => $file['name'] ?? '']));
        }

        $mime = $this->detectMime($file['tmp_name'], $file['name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new UploadRejected(t('media.type_not_allowed', ['name' => $file['name']]));
        }
        [$extension, $kind] = self::ALLOWED[$mime];

        $limit = $kind === 'image' ? self::MAX_IMAGE_BYTES : self::MAX_DOCUMENT_BYTES;
        if ((int) filesize($file['tmp_name']) > $limit) {
            throw new UploadRejected(t('media.too_large', ['name' => $file['name'], 'limit' => $limit / self::MB]));
        }

        return [$mime, $extension, $kind];
    }

    /**
     * Kích thước ảnh và ảnh thu nhỏ 400/1200 px (GIF động không tạo ảnh thu nhỏ).
     *
     * @return array{width: ?int, height: ?int, thumb_small_path: ?string, thumb_large_path: ?string}
     */
    private function imageDetails(string $storedPath, string $mime): array
    {
        [$width, $height] = getimagesize($this->absolute($storedPath)) ?: [null, null];
        $details = ['width' => $width, 'height' => $height, 'thumb_small_path' => null, 'thumb_large_path' => null];
        if ($mime === 'image/gif' && self::isAnimatedGif($this->absolute($storedPath))) {
            return $details;
        }

        $base = substr($storedPath, 0, strrpos($storedPath, '.'));
        $extension = pathinfo($storedPath, PATHINFO_EXTENSION);
        $details['thumb_small_path'] = $this->makeThumbnail($storedPath, "$base-400.$extension", self::THUMB_SMALL_WIDTH);
        $details['thumb_large_path'] = $this->makeThumbnail($storedPath, "$base-1200.$extension", self::THUMB_LARGE_WIDTH);

        return $details;
    }

    /** Loại MIME theo nội dung tệp; DOCX đôi khi được nhận là ZIP nên kiểm tra thêm cấu trúc bên trong. */
    private function detectMime(string $path, string $originalName): string
    {
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $isZip = in_array($mime, ['application/zip', 'application/octet-stream'], true);
        if ($isZip && str_ends_with(mb_strtolower($originalName), '.docx') && self::looksLikeDocx($path)) {
            return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }

        return $mime;
    }

    private static function looksLikeDocx(string $path): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $found = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $found;
    }

    /** GIF động có nhiều hơn một khối "Graphic Control Extension". */
    private static function isAnimatedGif(string $path): bool
    {
        return substr_count((string) file_get_contents($path), "\x21\xF9\x04") > 1;
    }

    private function storeFile(string $tmpPath, string $storedPath): void
    {
        $target = $this->absolute($storedPath);
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }
        // Tệp tải lên qua HTTP dùng move_uploaded_file; tệp tạo trong test/CLI thì chép.
        $ok = is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $target) : copy($tmpPath, $target);
        if (!$ok) {
            throw new UploadRejected(t('media.upload_failed', ['name' => basename($storedPath)]));
        }
    }

    /** Tạo ảnh thu nhỏ; ảnh gốc không rộng hơn thì dùng luôn ảnh gốc. Trả về đường dẫn. */
    private function makeThumbnail(string $sourcePath, string $targetPath, int $targetWidth): ?string
    {
        $source = @imagecreatefromstring((string) file_get_contents($this->absolute($sourcePath)));
        if ($source === false) {
            return null;
        }
        if (imagesx($source) <= $targetWidth) {
            return $sourcePath;
        }

        $resized = imagescale($source, $targetWidth);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $target = $this->absolute($targetPath);
        match (pathinfo($targetPath, PATHINFO_EXTENSION)) {
            'png' => imagepng($resized, $target),
            'webp' => imagewebp($resized, $target, 82),
            'gif' => imagegif($resized, $target),
            default => imagejpeg($resized, $target, 82),
        };

        return $targetPath;
    }

    private function absolute(string $relativePath): string
    {
        return $this->publicDir . '/' . $relativePath;
    }
}
