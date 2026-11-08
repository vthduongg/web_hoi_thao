<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Đổi link video/bản đồ người dùng dán vào nội dung thành khung nhúng co giãn (FR-011).
 *
 * - Một đoạn văn CHỈ chứa link YouTube (watch?v=, youtu.be/, /embed/) → iframe youtube-nocookie.com.
 * - Một đoạn văn CHỈ chứa link nhúng Google Maps (google.com/maps/embed?...) → iframe bản đồ.
 * - Iframe có sẵn được bọc trong <div class="embed-responsive"> (tỉ lệ 16:9, xem public.css).
 * Link nằm giữa câu văn hoặc thuộc trang khác được giữ nguyên. Chạy nhiều lần cho cùng kết quả.
 */
final class EmbedConverter
{
    private const WRAPPER_START = '<div class="embed-responsive">';

    public static function convert(string $html): string
    {
        $html = self::convertLinkParagraphs($html);

        return self::wrapIframes($html);
    }

    /** <p>link</p> hoặc <p><a href="link">link</a></p> → iframe (nếu link là YouTube/Google Maps). */
    private static function convertLinkParagraphs(string $html): string
    {
        return (string) preg_replace_callback(
            '#<p>\s*(?:<a\b[^>]*>)?\s*(https?://[^\s<"]+)\s*(?:</a>)?\s*</p>#i',
            static function (array $match): string {
                $src = self::embedSource(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                return $src === null ? $match[0] : self::iframe($src);
            },
            $html,
        );
    }

    /** Bọc iframe chưa được bọc; đổi YouTube sang youtube-nocookie.com. */
    private static function wrapIframes(string $html): string
    {
        return (string) preg_replace_callback(
            '#(' . preg_quote(self::WRAPPER_START, '#') . '\s*)?<iframe\b[^>]*\bsrc="([^"]+)"[^>]*>\s*</iframe>#i',
            static function (array $match): string {
                if ($match[1] !== '') {
                    return $match[0]; // đã được bọc
                }
                $src = self::embedSource(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? $match[2];

                return self::iframe($src);
            },
            $html,
        );
    }

    /** Địa chỉ nhúng tương ứng với link, hoặc null nếu link không phải YouTube / Google Maps embed. */
    private static function embedSource(string $url): ?string
    {
        if (preg_match('#^https?://(?:www\.)?(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube(?:-nocookie)?\.com/embed/)([A-Za-z0-9_-]+)#', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        }
        if (preg_match('#^https://www\.google\.com/maps/embed\?#', $url)) {
            return $url;
        }

        return null;
    }

    private static function iframe(string $src): string
    {
        $isMap = str_contains($src, 'google.com/maps');

        return self::WRAPPER_START
            . '<iframe src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" title="' . ($isMap ? 'Map' : 'Video') . '" width="560" height="315"></iframe>'
            . '</div>';
    }
}
