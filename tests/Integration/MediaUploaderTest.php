<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Services\MediaUploader;
use App\Services\UploadRejected;
use Tests\Support\DatabaseTestCase;

final class MediaUploaderTest extends DatabaseTestCase
{
    private string $publicDir;
    private string $tmpDir;
    private MediaUploader $uploader;

    protected function setUp(): void
    {
        parent::setUp();
        Clock::freeze('2026-03-10 08:00:00');
        \App\Core\Lang::setCurrent(\App\Core\Lang::load('vi', dirname(__DIR__, 2) . '/lang'));
        $this->publicDir = sys_get_temp_dir() . '/uploader-public-' . bin2hex(random_bytes(4));
        $this->tmpDir = sys_get_temp_dir() . '/uploader-tmp-' . bin2hex(random_bytes(4));
        mkdir($this->publicDir);
        mkdir($this->tmpDir);
        $this->uploader = new MediaUploader($this->db, $this->publicDir);
    }

    protected function tearDown(): void
    {
        foreach ([$this->publicDir, $this->tmpDir] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($dir);
        }
        parent::tearDown();
    }

    /** Giả lập một phần tử của $_FILES. */
    private function fileEntry(string $name, string $content): array
    {
        $path = $this->tmpDir . '/' . bin2hex(random_bytes(4));
        file_put_contents($path, $content);

        return ['name' => $name, 'tmp_name' => $path, 'size' => strlen($content), 'error' => UPLOAD_ERR_OK, 'type' => 'application/octet-stream'];
    }

    private function jpeg(int $width, int $height): string
    {
        $img = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($img);

        return (string) ob_get_clean();
    }

    public function test_accepts_jpeg_stores_random_name_and_creates_thumbnails(): void
    {
        $media = $this->uploader->upload($this->fileEntry('Ảnh hội trường.jpg', $this->jpeg(1600, 900)), null);

        self::assertSame('Ảnh hội trường.jpg', $media->originalName);
        self::assertSame('image', $media->kind);
        self::assertSame('image/jpeg', $media->mimeType);
        self::assertMatchesRegularExpression('#^uploads/2026/03/[a-f0-9]{32}\.jpg$#', $media->storedPath);
        self::assertFileExists($this->publicDir . '/' . $media->storedPath);
        self::assertSame([1600, 900], [$media->width, $media->height]);
        self::assertSame(400, getimagesize($this->publicDir . '/' . $media->thumbSmallPath)[0]);
        self::assertSame(1200, getimagesize($this->publicDir . '/' . $media->thumbLargePath)[0]);
        self::assertSame(1, (int) $this->db->value('SELECT COUNT(*) FROM media_files WHERE id = ?', [$media->id]));
    }

    public function test_accepts_png_webp_gif_and_pdf(): void
    {
        $img = imagecreatetruecolor(50, 50);
        foreach (['a.png' => 'imagepng', 'a.webp' => 'imagewebp', 'a.gif' => 'imagegif'] as $name => $fn) {
            ob_start();
            $fn($img);
            $media = $this->uploader->upload($this->fileEntry($name, (string) ob_get_clean()), null);
            self::assertSame('image', $media->kind, $name);
        }

        $pdf = $this->uploader->upload($this->fileEntry('Hướng dẫn.pdf', (string) file_get_contents(dirname(__DIR__) . '/fixtures/sample.pdf')), null);
        self::assertSame('document', $pdf->kind);
        self::assertStringEndsWith('.pdf', $pdf->storedPath);
        self::assertNull($pdf->thumbSmallPath);
    }

    public function test_accepts_docx(): void
    {
        $media = $this->uploader->upload($this->fileEntry('Mẫu bài.docx', (string) file_get_contents(dirname(__DIR__) . '/fixtures/sample.docx')), null);

        self::assertSame('document', $media->kind);
        self::assertStringEndsWith('.docx', $media->storedPath);
    }

    public function test_rejects_svg_even_when_renamed(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        foreach (['logo.svg', 'logo.png'] as $name) {
            try {
                $this->uploader->upload($this->fileEntry($name, $svg), null);
                self::fail("Phải từ chối $name");
            } catch (UploadRejected $e) {
                self::assertStringContainsString('JPG, PNG, WebP, GIF, PDF, DOC, DOCX', $e->getMessage());
            }
        }
    }

    public function test_rejects_php_renamed_as_jpg(): void
    {
        $this->expectException(UploadRejected::class);
        $this->uploader->upload($this->fileEntry('shell.jpg', '<?php echo "hacked"; ?>'), null);
    }

    public function test_rejects_image_over_5_mb(): void
    {
        $entry = $this->fileEntry('big.jpg', $this->jpeg(10, 10));
        file_put_contents($entry['tmp_name'], str_repeat("\0", 5 * 1024 * 1024 + 1 - filesize($entry['tmp_name'])), FILE_APPEND);
        $entry['size'] = filesize($entry['tmp_name']);

        try {
            $this->uploader->upload($entry, null);
            self::fail('Phải từ chối ảnh quá 5 MB');
        } catch (UploadRejected $e) {
            self::assertStringContainsString('5 MB', $e->getMessage());
        }
    }

    public function test_does_not_create_thumbnails_for_animated_gif(): void
    {
        $media = $this->uploader->upload($this->fileEntry('anim.gif', (string) file_get_contents(dirname(__DIR__) . '/fixtures/animated.gif')), null);

        self::assertNull($media->thumbSmallPath);
        self::assertNull($media->thumbLargePath);
    }

    public function test_upload_error_is_reported(): void
    {
        $entry = $this->fileEntry('a.jpg', '');
        $entry['error'] = UPLOAD_ERR_INI_SIZE;

        $this->expectException(UploadRejected::class);
        $this->uploader->upload($entry, null);
    }
}
