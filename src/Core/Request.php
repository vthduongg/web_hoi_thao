<?php

declare(strict_types=1);

namespace App\Core;

/** Dữ liệu một request HTTP. Tạo từ biến toàn cục bằng fromGlobals(), hoặc tạo tay trong test. */
final class Request
{
    /**
     * @param array<string, mixed> $query  tham số trên URL (?a=b)
     * @param array<string, mixed> $post   dữ liệu biểu mẫu POST
     * @param array<string, mixed> $files  tệp tải lên (dạng $_FILES)
     * @param array<string, string> $cookies
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $files = [],
        public readonly array $cookies = [],
        public readonly string $ip = '',
        public readonly bool $isHttps = false,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rawurldecode($path),
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $_SERVER['REMOTE_ADDR'] ?? '',
            ($_SERVER['HTTPS'] ?? 'off') !== 'off',
        );
    }

    /** Lấy giá trị từ POST rồi tới query; luôn trả chuỗi đã bỏ khoảng trắng đầu/cuối. */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /** @return array<mixed> */
    public function inputArray(string $key): array
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function isAdminArea(): bool
    {
        return $this->path === '/admin' || str_starts_with($this->path, '/admin/');
    }
}
