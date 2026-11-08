<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Sponsor;
use App\Models\SponsorTier;

/** Đọc nhà tài trợ theo hạng. */
final class SponsorRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<SponsorTier> các hạng có ít nhất một nhà tài trợ đang hiện, theo thứ tự */
    public function visibleTiers(int $conferenceId): array
    {
        $sponsors = array_map([Sponsor::class, 'fromRow'], $this->db->query(
            'SELECT s.*, f.stored_path AS logo_path, f.thumb_small_path AS logo_small_path
             FROM sponsors s JOIN media_files f ON f.id = s.logo_media_id AND f.deleted_at IS NULL
             WHERE s.conference_id = ? AND s.is_visible = 1 AND s.deleted_at IS NULL
             ORDER BY s.sort_order, s.id',
            [$conferenceId],
        ));

        $tiers = [];
        foreach ($this->tierOptions($conferenceId) as $tierId => $name) {
            $inTier = array_values(array_filter($sponsors, static fn (Sponsor $s): bool => $s->tierId === $tierId));
            if ($inTier !== []) {
                $tiers[] = new SponsorTier($tierId, $name, $inTier);
            }
        }

        return $tiers;
    }

    /** @return array<int, string> id hạng => tên, theo thứ tự (dùng cho ô chọn trong biểu mẫu) */
    public function tierOptions(int $conferenceId): array
    {
        $options = [];
        foreach ($this->db->query('SELECT id, name FROM sponsor_tiers WHERE conference_id = ? AND deleted_at IS NULL ORDER BY sort_order, id', [$conferenceId]) as $row) {
            $options[(int) $row['id']] = $row['name'];
        }

        return $options;
    }
}
