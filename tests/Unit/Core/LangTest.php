<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Lang;
use PHPUnit\Framework\TestCase;

final class LangTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = dirname(__DIR__, 3) . '/lang';
    }

    public function test_reads_english_and_vietnamese_files(): void
    {
        self::assertSame('Page not found', Lang::load('en', $this->dir)->get('error.not_found'));
        self::assertSame('Không tìm thấy trang', Lang::load('vi', $this->dir)->get('error.not_found'));
    }

    public function test_missing_key_returns_the_key_itself(): void
    {
        self::assertSame('does.not.exist', Lang::load('en', $this->dir)->get('does.not.exist'));
    }

    public function test_replaces_placeholders(): void
    {
        self::assertSame('Tối đa 5 ký tự.', Lang::load('vi', $this->dir)->get('validation.max', ['max' => 5]));
    }

    public function test_t_helper_uses_current_language(): void
    {
        Lang::setCurrent(Lang::load('en', $this->dir));
        self::assertSame('Page not found', t('error.not_found'));

        Lang::setCurrent(Lang::load('vi', $this->dir));
        self::assertSame('Không tìm thấy trang', t('error.not_found'));
    }

    public function test_unknown_language_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Lang::load('xx', $this->dir);
    }
}
