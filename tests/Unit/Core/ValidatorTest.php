<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Lang;
use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator(Lang::load('vi', dirname(__DIR__, 3) . '/lang'));
    }

    public function test_valid_data_has_no_errors(): void
    {
        $errors = $this->validator->validate(
            ['name' => 'Hội thảo', 'email' => 'a@b.vn', 'url' => 'https://x.vn', 'day' => '2026-03-15', 'status' => 'draft'],
            ['name' => 'required|max:255', 'email' => 'email', 'url' => 'url', 'day' => 'date', 'status' => 'in:draft,published'],
        );

        self::assertSame([], $errors);
    }

    public function test_required_field_reports_vietnamese_message(): void
    {
        $errors = $this->validator->validate(['name' => '   '], ['name' => 'required']);

        self::assertSame(['name' => 'Trường này là bắt buộc.'], $errors);
    }

    public function test_max_length_counts_unicode_characters(): void
    {
        self::assertSame([], $this->validator->validate(['t' => str_repeat('ệ', 5)], ['t' => 'max:5']));

        $errors = $this->validator->validate(['t' => str_repeat('ệ', 6)], ['t' => 'max:5']);
        self::assertSame(['t' => 'Tối đa 5 ký tự.'], $errors);
    }

    public function test_email_url_date_and_enum_rules(): void
    {
        $errors = $this->validator->validate(
            ['email' => 'not-an-email', 'url' => 'ftp://x.vn', 'day' => '2026-02-30', 'status' => 'deleted'],
            ['email' => 'email', 'url' => 'url', 'day' => 'date', 'status' => 'in:draft,published'],
        );

        self::assertSame([
            'email' => 'Email không hợp lệ.',
            'url' => 'Đường dẫn phải bắt đầu bằng http:// hoặc https://.',
            'day' => 'Ngày không hợp lệ.',
            'status' => 'Giá trị không hợp lệ.',
        ], $errors);
    }

    public function test_min_length(): void
    {
        self::assertSame([], $this->validator->validate(['p' => 'ổổổổổổổổ'], ['p' => 'min:8']));
        self::assertSame(['p' => 'Tối thiểu 8 ký tự.'], $this->validator->validate(['p' => 'short'], ['p' => 'min:8']));
    }

    public function test_optional_empty_fields_skip_format_rules(): void
    {
        self::assertSame([], $this->validator->validate(['email' => '', 'url' => null], ['email' => 'email', 'url' => 'url']));
    }

    public function test_reports_only_first_error_per_field(): void
    {
        $errors = $this->validator->validate(['email' => ''], ['email' => 'required|email']);

        self::assertSame(['email' => 'Trường này là bắt buộc.'], $errors);
    }
}
