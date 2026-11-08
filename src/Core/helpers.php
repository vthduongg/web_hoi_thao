<?php

declare(strict_types=1);

// Hàm tiện ích dùng trong template và controller.

use App\Core\Clock;
use App\Core\Lang;
use App\Core\View;

/** Thoát HTML — dùng cho MỌI giá trị in ra template. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Lấy chuỗi giao diện theo ngôn ngữ hiện tại (lang/en.php hoặc lang/vi.php). */
function t(string $key, array $params = []): string
{
    return Lang::current()->get($key, $params);
}

/** Mã ngôn ngữ giao diện hiện tại (en / vi), dùng cho thuộc tính lang của thẻ html. */
function current_locale(): string
{
    return Lang::current()->locale;
}

/**
 * Định dạng ngày giờ để hiển thị.
 *
 * Kiểu:
 *   'public'     → 15 March 2026 (trang công khai)
 *   'admin'      → 16/03/2026 00:30 (trang quản trị, có giờ)
 *   'admin_date' → 16/03/2026 (trang quản trị, chỉ ngày)
 *   'time'       → 08:30
 *   'input'      → 2026-03-16T00:30 (giá trị cho ô <input type="datetime-local">, giờ Việt Nam)
 *
 * Chuỗi dạng Y-m-d (chỉ ngày) được giữ nguyên ngày, không đổi múi giờ.
 * Giá trị có giờ được hiểu là UTC và đổi sang giờ Việt Nam.
 */
function format_date(DateTimeInterface|string|null $value, string $style = 'public'): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $format = match ($style) {
        'public' => 'j F Y',
        'admin' => 'd/m/Y H:i',
        'admin_date' => 'd/m/Y',
        'time' => 'H:i',
        'input' => 'Y-m-d\TH:i',
        default => throw new InvalidArgumentException("Kiểu định dạng ngày không tồn tại: $style"),
    };

    $vietnam = new DateTimeZone(Clock::VIETNAM_TIMEZONE);
    if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        $date = new DateTimeImmutable($value, $vietnam);
    } elseif (is_string($value) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
        $date = new DateTimeImmutable('2000-01-01 ' . $value, $vietnam);
    } else {
        $date = is_string($value) ? new DateTimeImmutable($value, new DateTimeZone('UTC')) : DateTimeImmutable::createFromInterface($value);
        $date = $date->setTimezone($vietnam);
    }

    return $date->format($format);
}

/** Giá trị ô datetime-local (giờ Việt Nam, "2026-03-10T09:00") hiển thị dạng "10/03/2026 09:00"; sai dạng → rỗng. */
function local_input_to_display(?string $localDateTime): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', (string) $localDateTime);

    return $date === false ? '' : $date->format('d/m/Y H:i');
}

/** Render một khối template con (partial) và trả về HTML. */
function partial(string $name, array $vars = []): string
{
    return View::current()->render($name, $vars);
}

/** Đường dẫn trong site, có tiền tố năm khi đang xem hội thảo lưu trữ. */
function url(string $path = '/'): string
{
    return View::current()->url($path);
}

/** Đường dẫn tới tệp tĩnh trong public/assets. */
function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

/** Đường dẫn công khai của tệp tải lên. */
function upload_url(?string $storedPath): string
{
    return $storedPath ? '/' . ltrim(str_replace('\\', '/', $storedPath), '/') : '';
}

/**
 * Thuộc tính srcset + sizes cho ảnh có thumbnail 400 px / 1200 px (tạo lúc tải lên).
 * Trả về chuỗi rỗng khi ảnh không có thumbnail (ảnh nhỏ hơn 400 px), trình duyệt dùng src như thường.
 * Ví dụ: <img src="..."<?= srcset_attr($small, $large, '240px') ?>>
 */
function srcset_attr(?string $smallPath, ?string $largePath, string $sizes): string
{
    $candidates = array_filter([
        $smallPath ? upload_url($smallPath) . ' 400w' : null,
        $largePath ? upload_url($largePath) . ' 1200w' : null,
    ]);
    if ($candidates === []) {
        return '';
    }

    return ' srcset="' . e(implode(', ', $candidates)) . '" sizes="' . e($sizes) . '"';
}

/**
 * Thêm loading="lazy" cho ảnh trong nội dung do trình soạn thảo tạo (HTML đã lọc bằng HTML Purifier,
 * vốn bỏ thuộc tính loading khi lưu). Ảnh đã có loading thì giữ nguyên.
 */
function lazy_images(string $sanitizedHtml): string
{
    return (string) preg_replace('/<img(?![^>]*\sloading=)\s/i', '<img loading="lazy" ', $sanitizedHtml);
}
