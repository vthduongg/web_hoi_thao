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

    public function test_empty_value_formats_to_empty_string(): void
    {
        self::assertSame('', format_date(null, 'public'));
    }
}
