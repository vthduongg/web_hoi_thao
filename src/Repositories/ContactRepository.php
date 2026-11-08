<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ContactPerson;

/** Đọc người liên hệ cho trang công khai. */
final class ContactRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<ContactPerson> */
    public function visibleForConference(int $conferenceId): array
    {
        return array_map([ContactPerson::class, 'fromRow'], $this->db->query(
            'SELECT * FROM contact_persons WHERE conference_id = ? AND is_visible = 1 AND deleted_at IS NULL ORDER BY sort_order, id',
            [$conferenceId],
        ));
    }
}
