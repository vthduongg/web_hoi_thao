# Hướng dẫn chỉnh giao diện

Dành cho người biết HTML/CSS cơ bản. Không cần cài công cụ build: sửa file, lưu, tải lại trình duyệt (Ctrl+F5) là thấy.
Không cần đọc code PHP xử lý trong `src/`.

## 1. Muốn sửa gì → mở file nào

| Muốn sửa | Mở file |
|---|---|
| Màu thương hiệu, font, cỡ chữ, khoảng cách, bo góc | `public/assets/css/theme.css` (chỉ chứa biến CSS) |
| Kiểu hiển thị trang công khai | `public/assets/css/public.css` (chia khối đánh số, mỗi khối có tiêu đề `/* ===== … ===== */`) |
| Kiểu hiển thị trang quản trị | `public/assets/css/admin.css` |
| Khung trang công khai | `templates/public/layout.php` |
| Logo, menu trên cùng | `templates/public/partials/header.php`, `templates/public/partials/menu.php` |
| Chân trang (footer) | `templates/public/partials/footer.php` |
| Trang chủ (slider, thông tin chính, khối nổi bật) | `templates/public/home.php`, `templates/public/partials/slider.php` |
| Trang nội dung | `templates/public/page.php` |
| Bảng mốc thời gian, bảng ban, thẻ diễn giả, chương trình, nhà tài trợ, liên hệ | `templates/public/modules/<tên khối>.php` |
| Dải "This is an archived conference" | `templates/public/partials/archive-banner.php` |
| Trang 404 / lỗi | `templates/public/404.php`, `templates/public/500.php` |
| Chữ cố định trên trang công khai (nút, nhãn) | `lang/en.php` |
| Chữ trong trang quản trị | `lang/vi.php` |

Nội dung do ban tổ chức nhập (tiêu đề, bài viết, ngày, tên diễn giả…) **không** nằm trong các file trên mà được sửa
trong trang quản trị `/admin`.

## 2. Đổi màu

Mở `public/assets/css/theme.css`, sửa giá trị biến. Ví dụ đổi màu chính từ đỏ sang xanh lá:

```css
--color-primary: #1e6b3a;        /* Màu chính */
--color-primary-dark: #154d2a;   /* Màu chính khi rê chuột: tối hơn màu chính khoảng 20% */
```

Mọi nơi dùng `var(--color-primary)` (thanh menu, nút, tiêu đề nhấn) đổi theo. **Không** viết mã màu trực tiếp
trong `public.css` hay template, nếu không lần sau đổi màu sẽ phải tìm khắp nơi.

### Kiểm tra độ tương phản (bắt buộc khi đổi màu chữ hoặc nền)

Chữ phải đọc được với người mắt kém: tỉ lệ tương phản **≥ 4.5 : 1** (chữ thường) giữa màu chữ và màu nền.

1. Mở <https://webaim.org/resources/contrastchecker/>.
2. *Foreground* = màu chữ, *Background* = màu nền. Các cặp cần kiểm:
   - `--color-text-inverse` (chữ trắng) trên `--color-primary` và `--color-primary-dark` (menu, nút chính);
   - `--color-accent` trên `--color-bg` (liên kết);
   - `--color-text` và `--color-text-muted` trên `--color-bg` và `--color-bg-alt`.
3. Ô **WCAG AA – Normal Text** phải ghi *Pass*. Nếu *Fail*, chọn màu tối hơn (với nền sáng) rồi kiểm lại.
4. Cập nhật chú thích tỉ lệ ở cuối dòng biến trong `theme.css` để người sau biết.

Màu vàng `--color-highlight` chỉ dùng làm điểm nhấn (gạch chân, viền), **không** dùng làm màu chữ trên nền trắng.

## 3. Đổi font

```css
--font-body: "Be Vietnam Pro", "Segoe UI", Arial, sans-serif;
```

Chọn font có đủ dấu tiếng Việt. Nếu dùng font không có sẵn trên máy, đặt tệp `.woff2` vào `public/assets/fonts/`
và khai báo `@font-face` ở đầu `theme.css` (website không tải font từ máy chủ ngoài, vì chính sách bảo mật CSP chặn).

## 4. Logo, favicon

Không cần sửa file: vào **Quản trị → Thông tin chung**, chọn logo / favicon từ thư viện tệp. Kích thước logo trên
header được đặt ở khối `/* ===== 3. Header và menu ===== */` trong `public.css`.

## 5. Ví dụ: thêm một khối mới vào trang chủ

Mục tiêu: thêm dải "Registration is open" phía trên khối nổi bật.

**Bước 1: thêm chữ vào `lang/en.php`** (không viết chữ thẳng trong template, có test tự động kiểm tra điều này):

```php
'home.registration_open' => 'Registration is open!',
'home.register_now' => 'Register now',
```

**Bước 2: thêm HTML vào `templates/public/home.php`**, ngay trước dòng `<?php if ($highlights !== []): ?>`:

```php
<section class="container registration-banner">
    <p class="registration-banner__text"><?= e(t('home.registration_open')) ?></p>
    <a class="button button--primary" href="/registration"><?= e(t('home.register_now')) ?></a>
</section>
```

Quy tắc khi viết template:
- Mọi giá trị in ra dùng `<?= e(...) ?>` để thoát HTML.
- Chỉ dùng `if`, `foreach`, `partial(...)`, `t(...)`, `format_date(...)`; không truy vấn dữ liệu trong template.
- Tên class đặt theo khối: `.ten-khoi`, `.ten-khoi__phan`, `.ten-khoi--bien-the`.

**Bước 3: thêm CSS vào `public/assets/css/public.css`**, trong khối `5b. Trang chủ`:

```css
.registration-banner {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  align-items: center;
  justify-content: space-between;
  padding-block: var(--space-3);
  background: var(--color-highlight-bg);
  border-left: 4px solid var(--color-highlight);
}
```

Chỉ dùng biến từ `theme.css`. Các mốc chiều rộng màn hình cố định: `600px` (điện thoại → máy tính bảng),
`900px` (hiện menu đầy đủ), `1200px` (độ rộng tối đa nội dung). Luôn thử ở độ rộng 360px (điện thoại).

**Bước 4: kiểm tra**

```powershell
composer test
```

Lệnh chạy cả test PHP lẫn test trình duyệt; nếu báo lỗi ở `LanguageTest` nghĩa là còn chữ viết thẳng trong template.

## 6. JavaScript

Mỗi chức năng một file trong `public/assets/js/` (menu điện thoại, slider, sao chép link…), không phụ thuộc nhau.
Phần tử do JavaScript điều khiển dùng thuộc tính `data-*` (ví dụ `data-menu-toggle`). Khi đổi tên class CSS thì
**giữ nguyên** các thuộc tính `data-*`. Sửa hành vi JavaScript thì sửa test tương ứng trong `tests/e2e/`.

Không viết `<script>` hay `style="..."` trong template hoặc trong nội dung trang: chính sách bảo mật (CSP) của trang
công khai sẽ chặn chúng.
