<?php

declare(strict_types=1);

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Lọc HTML từ trình soạn thảo trước khi lưu (chống XSS — research.md R6).
 *
 * Giữ định dạng thông thường (đoạn văn, danh sách, bảng, ảnh, liên kết);
 * chỉ cho iframe từ YouTube và Google Maps.
 */
final class HtmlSanitizer
{
    private const IFRAME_SOURCES = '%^https://(www\.youtube\.com/embed/|www\.youtube-nocookie\.com/embed/|www\.google\.com/maps/embed)%';

    private HTMLPurifier $purifier;

    public function __construct(string $cacheDir)
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', $cacheDir);
        $config->set('HTML.SafeIframe', true);
        $config->set('URI.SafeIframeRegexp', self::IFRAME_SOURCES);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('Attr.AllowedClasses', null);
        $config->set('Attr.EnableID', false);
        $config->set('HTML.TargetNoopener', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        // Bỏ thuộc tính style: CSP trang công khai (style-src 'self') chặn style viết trong thẻ (research.md R18)
        $config->set('CSS.AllowedProperties', []);

        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(?string $html): string
    {
        return $html === null || trim($html) === '' ? '' : trim($this->purifier->purify($html));
    }
}
