<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Conference;

/** Truy vấn bảng conferences. */
final class ConferenceRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** Hội thảo đang hiện hành (hiển thị ở đường dẫn chính). */
    public function current(): ?Conference
    {
        $row = $this->db->first('SELECT * FROM conferences WHERE is_current = 1');

        return $row === null ? null : Conference::fromRow($row);
    }

    public function find(int $id): ?Conference
    {
        $row = $this->db->first('SELECT * FROM conferences WHERE id = ?', [$id]);

        return $row === null ? null : Conference::fromRow($row);
    }

    public function findByYear(int $year): ?Conference
    {
        $row = $this->db->first('SELECT * FROM conferences WHERE year = ?', [$year]);

        return $row === null ? null : Conference::fromRow($row);
    }

    /** @return list<Conference> mọi hội thảo, mới nhất trước */
    public function all(): array
    {
        return array_map(
            [Conference::class, 'fromRow'],
            $this->db->query('SELECT * FROM conferences ORDER BY year DESC'),
        );
    }

    /** Đặt một hội thảo làm hiện hành (bỏ cờ của hội thảo cũ); đánh dấu đã từng hiện hành để được lưu trữ công khai. */
    public function makeCurrent(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $this->db->execute('UPDATE conferences SET is_current = 0, updated_at = SYSUTCDATETIME() WHERE is_current = 1 AND id <> ?', [$id]);
            $this->db->update('conferences', ['is_current' => 1, 'has_been_current' => 1], 'id = ?', [$id]);
        });
    }

    /** @return list<Conference> hội thảo đã từng hiện hành (trừ hội thảo đang hiện hành), mới nhất trước */
    public function archived(): array
    {
        return array_map(
            [Conference::class, 'fromRow'],
            $this->db->query('SELECT * FROM conferences WHERE has_been_current = 1 AND is_current = 0 ORDER BY year DESC'),
        );
    }

    /** @param array<string, mixed> $data cột => giá trị */
    public function update(int $id, array $data): void
    {
        $this->db->update('conferences', $data, 'id = ?', [$id]);
    }
}
