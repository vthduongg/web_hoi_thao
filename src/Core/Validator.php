<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Kiểm tra dữ liệu biểu mẫu phía server.
 *
 * Quy tắc viết dạng chuỗi, ngăn cách bằng "|": required, max:N, email, url, date, datetime, in:a,b,c, integer.
 * Mỗi trường chỉ báo lỗi đầu tiên gặp. Trường rỗng mà không "required" thì bỏ qua các quy tắc còn lại.
 */
final class Validator
{
    public function __construct(private readonly Lang $lang)
    {
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $rules
     * @return array<string, string> lỗi theo tên trường (rỗng = hợp lệ)
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $error = $this->firstError($value, explode('|', $ruleString));
            if ($error !== null) {
                $errors[$field] = $error;
            }
        }

        return $errors;
    }

    /** @param list<string> $rules */
    private function firstError(mixed $value, array $rules): ?string
    {
        $isEmpty = $value === null || $value === '' || $value === [];

        foreach ($rules as $rule) {
            [$name, $argument] = array_pad(explode(':', $rule, 2), 2, '');

            if ($name === 'required') {
                if ($isEmpty) {
                    return $this->lang->get('validation.required');
                }
                continue;
            }
            if ($isEmpty) {
                return null;
            }

            $error = match ($name) {
                'max' => mb_strlen((string) $value) > (int) $argument ? $this->lang->get('validation.max', ['max' => $argument]) : null,
                'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false ? $this->lang->get('validation.email') : null,
                'url' => self::isHttpUrl((string) $value) ? null : $this->lang->get('validation.url'),
                'date' => self::isDate((string) $value, 'Y-m-d') ? null : $this->lang->get('validation.date'),
                'datetime' => self::isDate((string) $value, 'Y-m-d\TH:i') ? null : $this->lang->get('validation.date'),
                'time' => self::isDate((string) $value, 'H:i') ? null : $this->lang->get('validation.time'),
                'in' => in_array((string) $value, explode(',', $argument), true) ? null : $this->lang->get('validation.in'),
                'integer' => filter_var($value, FILTER_VALIDATE_INT) === false ? $this->lang->get('validation.integer') : null,
                default => throw new \InvalidArgumentException("Quy tắc kiểm tra không tồn tại: $name"),
            };
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    public static function isHttpUrl(string $value): bool
    {
        return (bool) preg_match('#^https?://[^\s/$.?\#].[^\s]*$#i', $value);
    }

    private static function isDate(string $value, string $format): bool
    {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        return $date !== false && $date->format($format) === $value;
    }
}
