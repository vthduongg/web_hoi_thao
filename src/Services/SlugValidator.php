<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Quy tắc đường dẫn thân thiện (slug) của trang: chữ thường không dấu, số, dấu gạch ngang; tối đa 150 ký tự;
 * không trùng đường dẫn hệ thống và không phải số năm (vì /2025 là đường dẫn lưu trữ hội thảo).
 */
final class SlugValidator
{
    public const MAX_LENGTH = 150;
    private const RESERVED = ['admin', 'assets', 'uploads', 'past-conferences'];

    /** Bảng bỏ dấu tiếng Việt. */
    private const VIETNAMESE = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
        'e' => 'èéẹẻẽêềếệểễ',
        'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ',
        'u' => 'ùúụủũưừứựửữ',
        'y' => 'ỳýỵỷỹ',
        'd' => 'đ',
    ];

    /** Lỗi của slug: 'invalid' | 'too_long' | 'reserved', hoặc null nếu hợp lệ. */
    public static function problem(string $slug): ?string
    {
        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
            return 'invalid';
        }
        if (strlen($slug) > self::MAX_LENGTH) {
            return 'too_long';
        }
        if (in_array($slug, self::RESERVED, true) || preg_match('/^\d{4}$/', $slug)) {
            return 'reserved';
        }

        return null;
    }

    /** Gợi ý slug từ tiêu đề, ví dụ "Thông tin đi lại" → "thong-tin-di-lai". */
    public static function fromTitle(string $title): string
    {
        $text = mb_strtolower($title);
        foreach (self::VIETNAMESE as $plain => $accented) {
            $text = str_replace(mb_str_split($accented), $plain, $text);
        }
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
        $slug = substr($slug, 0, self::MAX_LENGTH);

        return $slug === '' ? 'page' : rtrim($slug, '-');
    }
}
