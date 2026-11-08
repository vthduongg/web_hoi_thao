<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Tìm nơi một tệp trong thư viện đang được dùng, để cảnh báo trước khi xoá (FR-036).
 *
 * Tìm tại thời điểm xoá (research.md R12): qua các cột khoá ngoại tới media_files và qua đường dẫn tệp
 * nằm trong nội dung HTML. Chỉ tính các dòng chưa bị xoá.
 */
final class MediaUsageFinder
{
    /** Cột khoá ngoại: [câu SQL lấy tên đối tượng (một tham số: id tệp), khoá chuỗi mô tả] */
    private const REFERENCES = [
        ['SELECT name FROM conferences WHERE logo_media_id = ?', 'usage.logo'],
        ['SELECT name FROM conferences WHERE favicon_media_id = ?', 'usage.favicon'],
        ['SELECT name FROM conferences WHERE program_file_media_id = ?', 'usage.program_file'],
        ['SELECT COALESCE(title, alt_text) AS name FROM slides WHERE media_id = ? AND deleted_at IS NULL', 'usage.slide'],
        ['SELECT full_name AS name FROM committee_members WHERE photo_media_id = ? AND deleted_at IS NULL', 'usage.member_photo'],
        ['SELECT full_name AS name FROM keynote_speakers WHERE photo_media_id = ? AND deleted_at IS NULL', 'usage.speaker_photo'],
        ['SELECT name FROM sponsors WHERE logo_media_id = ? AND deleted_at IS NULL', 'usage.sponsor_logo'],
        ['SELECT title AS name FROM pages WHERE featured_media_id = ? AND deleted_at IS NULL', 'usage.page_featured'],
    ];

    /** Nội dung HTML có thể chứa đường dẫn tệp: [câu SQL (một tham số: mẫu LIKE), khoá chuỗi mô tả] */
    private const HTML_COLUMNS = [
        ["SELECT title AS name FROM pages WHERE body_html LIKE ? ESCAPE '\\' AND deleted_at IS NULL", 'usage.page_body'],
        ["SELECT full_name AS name FROM keynote_speakers WHERE bio_html LIKE ? ESCAPE '\\' AND deleted_at IS NULL", 'usage.speaker_bio'],
        ["SELECT name FROM conferences WHERE home_intro_html LIKE ? ESCAPE '\\'", 'usage.home_intro'],
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return list<string> mô tả từng nơi dùng, ví dụ "Nội dung trang — Travel Info" */
    public function find(int $mediaId): array
    {
        $storedPath = $this->db->value('SELECT stored_path FROM media_files WHERE id = ?', [$mediaId]);
        if ($storedPath === null) {
            return [];
        }

        $usages = [];
        foreach (self::REFERENCES as [$sql, $labelKey]) {
            $usages = [...$usages, ...$this->describe($sql, [$mediaId], $labelKey)];
        }
        // Đường dẫn nằm trong thuộc tính HTML: src="/uploads/..." (có dấu " hai đầu để không khớp nhầm tên tương tự)
        $pattern = Database::likeContains('"/' . $storedPath . '"');
        foreach (self::HTML_COLUMNS as [$sql, $labelKey]) {
            $usages = [...$usages, ...$this->describe($sql, [$pattern], $labelKey)];
        }

        return $usages;
    }

    /** @return list<string> */
    private function describe(string $sql, array $params, string $labelKey): array
    {
        return array_map(
            static fn (array $row): string => t($labelKey) . ' — ' . $row['name'],
            $this->db->query($sql, $params),
        );
    }
}
