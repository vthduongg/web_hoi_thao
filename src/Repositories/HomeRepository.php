<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Highlight;
use App\Models\Slide;

/** Truy vấn dữ liệu trang chủ: slider và khối nổi bật. */
final class HomeRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<Slide> */
    public function visibleSlides(int $conferenceId): array
    {
        return array_map([Slide::class, 'fromRow'], $this->db->query(
            'SELECT s.*, m.stored_path, m.thumb_large_path
             FROM slides s JOIN media_files m ON m.id = s.media_id
             WHERE s.conference_id = ? AND s.is_visible = 1 AND s.deleted_at IS NULL AND m.deleted_at IS NULL
             ORDER BY s.sort_order, s.id',
            [$conferenceId],
        ));
    }

    /** @return list<Highlight> */
    public function visibleHighlights(int $conferenceId): array
    {
        return array_map([Highlight::class, 'fromRow'], $this->db->query(
            'SELECT * FROM highlights WHERE conference_id = ? AND is_visible = 1 AND deleted_at IS NULL ORDER BY sort_order, id',
            [$conferenceId],
        ));
    }
}
