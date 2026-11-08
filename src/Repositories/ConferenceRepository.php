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

    /** @param array<string, mixed> $data cột => giá trị */
    public function update(int $id, array $data): void
    {
        $this->db->update('conferences', $data, 'id = ?', [$id]);
    }
}
