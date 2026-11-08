<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Đọc cấu hình từ file .env (dạng KEY=VALUE) và biến môi trường.
 *
 * Biến môi trường thật (đặt bởi hệ điều hành / web server) luôn được ưu tiên hơn giá trị trong file.
 */
final class Env
{
    public static function loadFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = self::stripQuotes($value);

            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }

    private static function stripQuotes(string $value): string
    {
        $first = $value[0] ?? '';
        if (($first === '"' || $first === "'") && str_ends_with($value, $first) && strlen($value) >= 2) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
