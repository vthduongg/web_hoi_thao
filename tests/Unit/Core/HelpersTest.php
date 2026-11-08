<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function test_e_escapes_html_special_characters(): void
    {
        self::assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; &amp; &#039;', e('<script>alert("x")</script> & \''));
    }

    public function test_srcset_lists_available_thumbnails(): void
    {
        self::assertSame(
            ' srcset="/uploads/a-400.jpg 400w, /uploads/a-1200.jpg 1200w" sizes="100vw"',
            srcset_attr('uploads/a-400.jpg', 'uploads/a-1200.jpg', '100vw'),
        );
        self::assertSame(' srcset="/uploads/a-400.jpg 400w" sizes="240px"', srcset_attr('uploads/a-400.jpg', null, '240px'));
    }

    public function test_lazy_images_adds_loading_once(): void
    {
        self::assertSame(
            '<p><img loading="lazy" src="/a.jpg" alt="A"> <img loading="eager" src="/b.jpg" alt="B"></p>',
            lazy_images('<p><img src="/a.jpg" alt="A"> <img loading="eager" src="/b.jpg" alt="B"></p>'),
        );
        self::assertSame('<p>No images</p>', lazy_images('<p>No images</p>'));
    }

    public function test_srcset_is_empty_without_thumbnails(): void
    {
        self::assertSame('', srcset_attr(null, null, '100vw'));
    }

    public function test_e_accepts_null_and_numbers(): void
    {
        self::assertSame('', e(null));
        self::assertSame('2026', e(2026));
    }

    public function test_public_format_is_day_month_name_year(): void
    {
        self::assertSame('15 March 2026', format_date('2026-03-15', 'public'));
    }

    public function test_date_only_values_are_not_shifted_by_timezone(): void
    {
        self::assertSame('15/03/2026', format_date('2026-03-15', 'admin_date'));
    }

    public function test_admin_format_converts_utc_to_vietnam_time(): void
    {
        $utc = new DateTimeImmutable('2026-03-15 17:30:00', new DateTimeZone('UTC'));

        self::assertSame('16/03/2026 00:30', format_date($utc, 'admin'));
        self::assertSame('16/03/2026 00:30', format_date('2026-03-15 17:30:00', 'admin'));
    }

    public function test_input_format_is_vietnam_time_for_datetime_local_fields(): void
    {
        self::assertSame('2026-03-10T08:30', format_date('2026-03-10 01:30:00', 'input'));
    }

    public function test_empty_value_formats_to_empty_string(): void
    {
        self::assertSame('', format_date(null, 'public'));
    }
}
