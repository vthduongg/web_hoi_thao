<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SlugValidator;
use PHPUnit\Framework\TestCase;

final class SlugValidatorTest extends TestCase
{
    public function test_accepts_lowercase_letters_digits_and_dashes(): void
    {
        self::assertNull(SlugValidator::problem('call-for-papers'));
        self::assertNull(SlugValidator::problem('program-2027'));
    }

    public function test_rejects_other_characters(): void
    {
        foreach (['Call-For-Papers', 'call for papers', 'hội-thảo', 'a/b', 'a.b', '', '-'] as $slug) {
            self::assertSame('invalid', SlugValidator::problem($slug), $slug);
        }
    }

    public function test_rejects_slugs_longer_than_150_characters(): void
    {
        self::assertNull(SlugValidator::problem(str_repeat('a', 150)));
        self::assertSame('too_long', SlugValidator::problem(str_repeat('a', 151)));
    }

    public function test_rejects_reserved_words_and_four_digit_years(): void
    {
        foreach (['admin', 'assets', 'uploads', 'past-conferences', '2025'] as $slug) {
            self::assertSame('reserved', SlugValidator::problem($slug), $slug);
        }
        self::assertSame('invalid', SlugValidator::problem('robots.txt'));
        self::assertSame('invalid', SlugValidator::problem('sitemap.xml'));
        self::assertNull(SlugValidator::problem('20255'));
    }

    public function test_builds_slug_from_vietnamese_title(): void
    {
        self::assertSame('hoi-thao', SlugValidator::fromTitle('Hội thảo'));
        self::assertSame('thong-tin-di-lai-luu-tru', SlugValidator::fromTitle('Thông tin đi lại & lưu trú'));
        self::assertSame('call-for-papers-2027', SlugValidator::fromTitle('  Call for Papers — 2027!  '));
        self::assertSame('duong-ve-ha-noi', SlugValidator::fromTitle('ĐƯỜNG VỀ HÀ NỘI'));
    }

    public function test_title_without_usable_characters_gives_fallback(): void
    {
        self::assertSame('page', SlugValidator::fromTitle('!!!'));
    }
}
