<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use DateTimeZone;
use ZipArchive;

/**
 * Sao lưu hằng ngày (FR-043, research.md R10):
 * - CSDL: BACKUP DATABASE ra tệp conference-YYYYMMDD.bak (đường dẫn nhìn từ máy SQL Server).
 * - Tệp tải lên: nén public/uploads thành uploads-YYYYMMDD.zip.
 * - Chỉ giữ 30 bản mới nhất của mỗi loại.
 *
 * Chế độ thử ($dryRun): không chạy BACKUP DATABASE mà chỉ ghi câu lệnh vào $executedCommands (dùng trong test).
 */
final class BackupService
{
    public const KEEP_COPIES = 30;

    /** @var list<string> câu lệnh SQL sao lưu đã chạy (hoặc đã định chạy, ở chế độ thử) */
    public array $executedCommands = [];

    public function __construct(
        private readonly Database $db,
        private readonly string $databaseName,
        private readonly string $publicDir,
        private readonly string $backupDir,
        private readonly bool $dryRun = false,
    ) {
    }

    /**
     * @return array{database: string, uploads: string} đường dẫn hai tệp sao lưu
     * @throws BackupFailed
     */
    public function run(): array
    {
        if (!is_dir($this->backupDir) || !is_writable($this->backupDir)) {
            throw new BackupFailed(t('backup.folder_missing', ['folder' => $this->backupDir]));
        }
        $date = Clock::now()->setTimezone(new DateTimeZone(Clock::VIETNAM_TIMEZONE))->format('Ymd');

        $databaseFile = $this->backupDir . DIRECTORY_SEPARATOR . "conference-$date.bak";
        $this->backupDatabase($databaseFile);
        $uploadsFile = $this->backupDir . DIRECTORY_SEPARATOR . "uploads-$date.zip";
        $this->zipUploads($uploadsFile);

        $this->keepNewest('conference-*.bak');
        $this->keepNewest('uploads-*.zip');

        return ['database' => $databaseFile, 'uploads' => $uploadsFile];
    }

    private function backupDatabase(string $file): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $this->databaseName)) {
            throw new BackupFailed(t('backup.bad_database_name'));
        }
        $sql = "BACKUP DATABASE [{$this->databaseName}] TO DISK = N'" . str_replace("'", "''", $file) . "' WITH INIT, COPY_ONLY";
        $this->executedCommands[] = $sql;
        if ($this->dryRun) {
            return;
        }

        try {
            $this->db->exec($sql);
        } catch (\Throwable $e) {
            throw new BackupFailed(t('backup.database_failed', ['error' => $e->getMessage()]), 0, $e);
        }
    }

    private function zipUploads(string $zipFile): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new BackupFailed(t('backup.zip_failed', ['file' => $zipFile]));
        }
        $uploadsDir = $this->publicDir . '/uploads';
        if (is_dir($uploadsDir)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadsDir, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $relative = 'uploads/' . str_replace('\\', '/', substr($file->getPathname(), strlen($uploadsDir) + 1));
                    $zip->addFile($file->getPathname(), $relative);
                }
            }
        }
        $zip->close();
    }

    /** Xoá bản sao lưu cũ, chỉ giữ KEEP_COPIES bản mới nhất (tên tệp có ngày nên sắp theo tên là theo thời gian). */
    private function keepNewest(string $pattern): void
    {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . $pattern) ?: [];
        sort($files, SORT_STRING);
        foreach (array_slice($files, 0, max(0, count($files) - self::KEEP_COPIES)) as $old) {
            unlink($old);
        }
    }
}
