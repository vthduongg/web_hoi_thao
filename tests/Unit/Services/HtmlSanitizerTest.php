<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

final class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new HtmlSanitizer(sys_get_temp_dir() . '/htmlpurifier-test');
    }

    public function test_keeps_tables_links_and_images(): void
    {
        $html = '<table><tbody><tr><td>A</td></tr></tbody></table>'
            . '<a href="https://example.com/x">Link</a>'
            . '<img src="/uploads/2026/01/a.jpg" alt="Ảnh">';

        $clean = $this->sanitizer->clean($html);

        self::assertStringContainsString('<table>', $clean);
        self::assertStringContainsString('href="https://example.com/x"', $clean);
        self::assertStringContainsString('<img src="/uploads/2026/01/a.jpg" alt="Ảnh"', $clean);
    }

    public function test_removes_scripts_event_handlers_and_javascript_urls(): void
    {
        $html = '<p onclick="steal()">Hi</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>';

        $clean = $this->sanitizer->clean($html);

        self::assertStringNotContainsString('script', $clean);
        self::assertStringNotContainsString('onclick', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
        self::assertStringContainsString('<p>Hi</p>', $clean);
    }

    public function test_keeps_iframes_from_allowed_hosts(): void
    {
        foreach ([
            'https://www.youtube.com/embed/abc123',
            'https://www.youtube-nocookie.com/embed/abc123',
            'https://www.google.com/maps/embed?pb=xyz',
        ] as $src) {
            $clean = $this->sanitizer->clean('<iframe src="' . $src . '" width="560" height="315"></iframe>');
            self::assertStringContainsString('<iframe', $clean, $src);
            self::assertStringContainsString(htmlspecialchars($src), $clean, $src);
        }
    }

    public function test_removes_iframes_from_other_hosts(): void
    {
        $clean = $this->sanitizer->clean('<iframe src="https://evil.example.com/embed"></iframe>');

        self::assertStringNotContainsString('evil.example.com', $clean);
    }

    public function test_keeps_vietnamese_text(): void
    {
        self::assertSame('<p>Hội thảo khoa học quốc tế</p>', $this->sanitizer->clean('<p>Hội thảo khoa học quốc tế</p>'));
    }
}
