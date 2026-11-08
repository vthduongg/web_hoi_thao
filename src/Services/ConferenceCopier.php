<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;

/**
 * Sao chép một hội thảo sang năm mới (FR-038, bảng "Sao chép hội thảo" trong data-model.md).
 *
 * Được chép: thông tin chung, trang (về bản nháp) + thẻ, menu, slider, khối nổi bật, mốc thời gian,
 * ban + thành viên + việc gắn ban vào trang, hạng + nhà tài trợ, người liên hệ.
 * KHÔNG chép: diễn giả, chương trình, lịch sử phiên bản, chuyển hướng slug, nhật ký. Tệp thư viện dùng chung.
 * Bản ghi đang nằm trong thùng rác không được chép. Mọi việc chạy trong MỘT transaction.
 *
 * Mỗi bước là một hàm protected để dễ đọc (và để test giả lập lỗi giữa chừng).
 */
class ConferenceCopier
{
    /** @var array<int, int> id trang cũ → id trang mới */
    private array $pageMap = [];

    public function __construct(private readonly Database $db, private readonly ActivityLogger $logger)
    {
    }

    /** @return int id hội thảo mới */
    public function copy(int $sourceId, int $newYear, ?int $userId): int
    {
        if ($newYear < 1000 || $newYear > 9999) {
            throw new InvalidArgumentException(t('conferences.year_invalid'));
        }
        if ($this->db->value('SELECT id FROM conferences WHERE year = ?', [$newYear]) !== null) {
            throw new InvalidArgumentException(t('settings.year_taken'));
        }
        $source = $this->db->first('SELECT * FROM conferences WHERE id = ?', [$sourceId]) ?? throw new InvalidArgumentException('Không có hội thảo nguồn.');
        $this->pageMap = [];

        $newId = $this->db->transaction(function () use ($source, $newYear): int {
            $toId = $this->copyConference($source, $newYear);
            $fromId = (int) $source['id'];
            $this->copyPages($fromId, $toId);
            $this->copyMenu($fromId, $toId);
            $this->copySimpleRows('slides', $fromId, $toId);
            $this->copySimpleRows('highlights', $fromId, $toId);
            $this->copySimpleRows('important_dates', $fromId, $toId);
            $this->copyCommittees($fromId, $toId);
            $this->copySponsors($fromId, $toId);
            $this->copyContacts($fromId, $toId);

            return $toId;
        });
        $this->logger->log($userId, 'copy_conference', 'conferences', $newId, $source['name'] . ' → ' . $newYear, $newId);

        return $newId;
    }

    protected function copyConference(array $source, int $newYear): int
    {
        $oldYear = (string) $source['year'];
        $row = self::withoutIds($source);
        $row['year'] = $newYear;
        $row['name'] = str_replace($oldYear, (string) $newYear, $source['name']);
        $row['copyright_text'] = $source['copyright_text'] === null ? null : str_replace($oldYear, (string) $newYear, $source['copyright_text']);
        $row['is_current'] = 0;
        $row['has_been_current'] = 0;
        $row['program_file_media_id'] = null;

        return $this->db->insert('conferences', $row);
    }

    protected function copyPages(int $fromId, int $toId): void
    {
        foreach ($this->liveRows('pages', $fromId) as $page) {
            $row = self::withoutIds($page);
            $row['conference_id'] = $toId;
            $row['status'] = 'draft';
            $row['published_at'] = null;
            $row['version'] = 1;
            $newId = $this->db->insert('pages', $row);
            $this->pageMap[(int) $page['id']] = $newId;
            foreach ($this->db->query('SELECT tag FROM page_tags WHERE page_id = ?', [$page['id']]) as $tag) {
                $this->db->insertRow('page_tags', ['page_id' => $newId, 'tag' => $tag['tag']]);
            }
        }
    }

    /** Mục cấp 1 trước, mục con sau, để ánh xạ được parent_id. */
    protected function copyMenu(int $fromId, int $toId): void
    {
        $itemMap = [];
        $items = $this->db->query('SELECT * FROM menu_items WHERE conference_id = ? AND deleted_at IS NULL ORDER BY CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END, sort_order', [$fromId]);
        foreach ($items as $item) {
            if ($item['parent_id'] !== null && !isset($itemMap[(int) $item['parent_id']])) {
                continue; // mục cha đang trong thùng rác
            }
            $row = self::withoutIds($item);
            $row['conference_id'] = $toId;
            $row['parent_id'] = $item['parent_id'] === null ? null : $itemMap[(int) $item['parent_id']];
            $row['page_id'] = $item['page_id'] === null ? null : ($this->pageMap[(int) $item['page_id']] ?? null);
            $itemMap[(int) $item['id']] = $this->db->insert('menu_items', $row);
        }
    }

    /** Bảng chỉ có conference_id, không có quan hệ khác cần ánh xạ. */
    protected function copySimpleRows(string $table, int $fromId, int $toId): void
    {
        foreach ($this->liveRows($table, $fromId) as $source) {
            $this->db->insert($table, ['conference_id' => $toId] + self::withoutIds($source));
        }
    }

    protected function copyCommittees(int $fromId, int $toId): void
    {
        foreach ($this->liveRows('committees', $fromId) as $committee) {
            $newCommittee = $this->db->insert('committees', ['conference_id' => $toId] + self::withoutIds($committee));
            foreach ($this->db->query('SELECT * FROM committee_members WHERE committee_id = ? AND deleted_at IS NULL', [$committee['id']]) as $member) {
                $this->db->insert('committee_members', ['committee_id' => $newCommittee] + self::withoutIds($member));
            }
            foreach ($this->db->query('SELECT page_id, sort_order FROM page_committees WHERE committee_id = ?', [$committee['id']]) as $link) {
                if (isset($this->pageMap[(int) $link['page_id']])) {
                    $this->db->insertRow('page_committees', ['page_id' => $this->pageMap[(int) $link['page_id']], 'committee_id' => $newCommittee, 'sort_order' => $link['sort_order']]);
                }
            }
        }
    }

    /** Nhà tài trợ thuộc hạng đang trong thùng rác thì bỏ qua. */
    protected function copySponsors(int $fromId, int $toId): void
    {
        $tierMap = [];
        foreach ($this->liveRows('sponsor_tiers', $fromId) as $tier) {
            $tierMap[(int) $tier['id']] = $this->db->insert('sponsor_tiers', ['conference_id' => $toId] + self::withoutIds($tier));
        }
        foreach ($this->liveRows('sponsors', $fromId) as $sponsor) {
            if (isset($tierMap[(int) $sponsor['sponsor_tier_id']])) {
                $this->db->insert('sponsors', ['conference_id' => $toId, 'sponsor_tier_id' => $tierMap[(int) $sponsor['sponsor_tier_id']]] + self::withoutIds($sponsor));
            }
        }
    }

    protected function copyContacts(int $fromId, int $toId): void
    {
        $this->copySimpleRows('contact_persons', $fromId, $toId);
    }

    /** @return list<array<string, mixed>> dòng chưa xoá của hội thảo nguồn (thứ tự hiển thị được chép theo cột sort_order) */
    private function liveRows(string $table, int $conferenceId): array
    {
        return $this->db->query("SELECT * FROM $table WHERE conference_id = ? AND deleted_at IS NULL ORDER BY id", [$conferenceId]);
    }

    /** Bỏ các cột do CSDL tự đặt hoặc sẽ được gán lại (id, khoá tới hội thảo, thời điểm, xoá mềm). */
    private static function withoutIds(array $row): array
    {
        unset($row['id'], $row['conference_id'], $row['committee_id'], $row['sponsor_tier_id'], $row['created_at'], $row['updated_at'], $row['deleted_at'], $row['deleted_by']);

        return $row;
    }
}
