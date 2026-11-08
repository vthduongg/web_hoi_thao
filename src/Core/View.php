<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Render template PHP trong thư mục templates/.
 *
 * Template chỉ hiển thị dữ liệu (xem contracts/ui-theme.md). Biến truyền vào trở thành biến cục bộ.
 */
final class View
{
    private static ?View $current = null;

    /** Tiền tố đường dẫn, ví dụ "/2025" khi đang xem hội thảo lưu trữ. */
    private string $urlPrefix = '';

    /** @var array<string, mixed> biến dùng chung cho mọi template (hội thảo, menu, người dùng...) */
    private array $shared = [];

    public function __construct(private readonly string $templateDir)
    {
        self::$current = $this;
    }

    public static function current(): self
    {
        if (self::$current === null) {
            throw new RuntimeException('View chưa được khởi tạo.');
        }

        return self::$current;
    }

    public function share(string $name, mixed $value): void
    {
        $this->shared[$name] = $value;
    }

    public function shared(string $name, mixed $default = null): mixed
    {
        return $this->shared[$name] ?? $default;
    }

    public function setUrlPrefix(string $prefix): void
    {
        $this->urlPrefix = rtrim($prefix, '/');
    }

    public function url(string $path): string
    {
        if (preg_match('#^(https?:)?//#', $path)) {
            return $path;
        }
        $path = '/' . ltrim($path, '/');

        return $this->urlPrefix === '' ? $path : ($path === '/' ? $this->urlPrefix : $this->urlPrefix . $path);
    }

    /** Render một template (không có layout). */
    public function render(string $template, array $vars = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Không tìm thấy template: $template");
        }

        $vars = array_merge($this->shared, $vars);

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        })($file, $vars);
    }

    /** Render template nội dung rồi đặt vào layout (biến $content trong layout). */
    public function page(string $layout, string $template, array $vars = []): string
    {
        $content = $this->render($template, $vars);

        return $this->render($layout, array_merge($vars, ['content' => $content]));
    }
}
