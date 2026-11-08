<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Chuỗi giao diện theo ngôn ngữ (constitution, Nguyên tắc III).
 *
 * lang/en.php dùng cho trang công khai, lang/vi.php dùng cho trang quản trị.
 * Khoá dạng "nhóm.tên"; giá trị có thể chứa :tham_so để thay thế.
 */
final class Lang
{
    private static ?Lang $current = null;

    /** @param array<string, string> $strings */
    private function __construct(public readonly string $locale, private readonly array $strings)
    {
    }

    public static function load(string $locale, string $directory): self
    {
        $file = $directory . '/' . $locale . '.php';
        if (!preg_match('/^[a-z]{2}$/', $locale) || !is_file($file)) {
            throw new InvalidArgumentException("Không có file ngôn ngữ: $locale");
        }

        return new self($locale, require $file);
    }

    public static function setCurrent(self $lang): void
    {
        self::$current = $lang;
    }

    public static function current(): self
    {
        if (self::$current === null) {
            throw new \LogicException('Chưa chọn ngôn ngữ giao diện.');
        }

        return self::$current;
    }

    /** @param array<string, string|int> $params */
    public function get(string $key, array $params = []): string
    {
        $text = $this->strings[$key] ?? $key;
        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    public function has(string $key): bool
    {
        return isset($this->strings[$key]);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->strings;
    }
}
