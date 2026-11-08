<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\KeynoteSpeaker;

/** Đọc diễn giả chính cho trang công khai. */
final class SpeakerRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<KeynoteSpeaker> */
    public function visibleForConference(int $conferenceId): array
    {
        return array_map([KeynoteSpeaker::class, 'fromRow'], $this->db->query(
            'SELECT s.*, f.stored_path AS photo_path, f.thumb_small_path AS photo_small_path
             FROM keynote_speakers s LEFT JOIN media_files f ON f.id = s.photo_media_id AND f.deleted_at IS NULL
             WHERE s.conference_id = ? AND s.is_visible = 1 AND s.deleted_at IS NULL
             ORDER BY s.sort_order, s.id',
            [$conferenceId],
        ));
    }
}
