<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Chạy các tệp database/migrations/*.sql chưa chạy, theo thứ tự tên tệp.
 *
 * Mỗi tệp chạy trong một transaction; tệp có thể chia nhiều khối bằng dòng chỉ có chữ "GO".
 * Tệp lỗi bị rollback và dừng ngay, các tệp sau không chạy.
 */
final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $directory)
    {
    }

    /** @return list<string> tên các tệp vừa được áp dụng */
    public function run(): array
    {
        $this->ensureMigrationsTable();
        $applied = array_column($this->db->query('SELECT filename FROM schema_migrations'), 'filename');

        $files = glob($this->directory . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $newlyApplied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $this->applyFile($file, $name);
            $newlyApplied[] = $name;
        }

        return $newlyApplied;
    }

    private function applyFile(string $file, string $name): void
    {
        $batches = preg_split('/^\s*GO\s*$/mi', (string) file_get_contents($file));

        try {
            $this->db->transaction(function () use ($batches, $name): void {
                foreach ($batches as $batch) {
                    if (trim($batch) !== '') {
                        $this->db->exec($batch);
                    }
                }
                $this->db->execute('INSERT INTO schema_migrations (filename, applied_at) VALUES (?, SYSUTCDATETIME())', [$name]);
            });
        } catch (\Throwable $e) {
            throw new RuntimeException("Migration lỗi ở tệp $name: " . $e->getMessage(), 0, $e);
        }
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->exec(
            "IF OBJECT_ID('schema_migrations', 'U') IS NULL
             CREATE TABLE schema_migrations (
                 filename NVARCHAR(255) NOT NULL PRIMARY KEY,
                 applied_at DATETIME2 NOT NULL
             )"
        );
    }
}
