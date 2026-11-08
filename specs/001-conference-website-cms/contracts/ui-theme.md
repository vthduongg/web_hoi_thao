# Contract: Giao diện dễ chỉnh sửa cho người mới

Mục tiêu: người chỉ biết HTML/CSS cơ bản đổi được màu, font, logo, bố cục một khối mà **không cần cài công cụ
build** và không phải đọc code PHP xử lý.

## Bản đồ "muốn sửa gì → mở file nào"

| Muốn sửa | File |
|---|---|
| Màu thương hiệu, font, cỡ chữ, khoảng cách, bo góc | `public/assets/css/theme.css` (chỉ chứa biến CSS) |
| Kiểu hiển thị trang công khai | `public/assets/css/public.css` (chia khối, mỗi khối có comment tiêu đề) |
| Kiểu hiển thị trang quản trị | `public/assets/css/admin.css` |
| Khung trang công khai (header, footer) | `templates/public/layout.php`, `templates/public/partials/header.php`, `footer.php` |
| Trang chủ | `templates/public/home.php` |
| Bảng mốc, bảng ban, thẻ diễn giả… | `templates/public/modules/{module}.php` |
| Chữ cố định trên trang công khai (nút, nhãn) | `lang/en.php` |
| Chữ trong trang quản trị | `lang/vi.php` |

## Quy tắc cho template

- Template chỉ **hiển thị** dữ liệu đã chuẩn bị sẵn; không truy vấn CSDL, không xử lý nghiệp vụ.
- Mọi giá trị in ra dùng `<?= e($value) ?>` (thoát HTML). Chỉ nội dung đã lọc từ trình soạn thảo dùng
  `<?= $page->bodyHtml ?>` và có comment `// đã lọc bằng HTML Purifier`.
- Chỉ dùng `if`, `foreach`, gọi `partial('ten-khoi', [...])`, `t('khoa')`, `format_date(...)`.
- Đầu mỗi template có comment liệt kê biến được truyền vào, ví dụ:
  `<?php /** @var Conference $conference  @var ImportantDate[] $dates */ ?>`.

## Quy tắc CSS

- Không dùng framework CSS, không preprocessor, không bước build.
- Mọi màu/font/khoảng cách lấy từ biến trong `theme.css` (`var(--color-primary)`); `public.css` không viết mã màu cứng.
- Tên class theo khối: `.khoi`, `.khoi__phan`, `.khoi--bien-the` (tên tiếng Anh, ví dụ `.speaker-card__name`).
- Breakpoint cố định: `600px` (điện thoại → máy tính bảng), `900px` (menu đầy đủ), `1200px` (độ rộng tối đa nội dung).
- Tương phản chữ ≥ 4.5:1 với các màu mặc định trong `theme.css`; khi đổi màu cần kiểm tra lại (hướng dẫn trong
  `docs/huong-dan-giao-dien.md`).

## Quy tắc JavaScript

- JavaScript thuần, mỗi chức năng một file trong `public/assets/js/`, không phụ thuộc lẫn nhau.
- Trang công khai vẫn đọc được đầy đủ khi tắt JavaScript (menu con mở bằng CSS `:focus-within` làm dự phòng).
- Mỗi file JS có test Playwright tương ứng trong `tests/e2e/` (Nguyên tắc II); sửa hành vi JS thì sửa test cùng lúc.
- Phần tử được JS điều khiển dùng thuộc tính `data-*` (ví dụ `data-menu-toggle`, `data-sortable`) để test chọn ổn định,
  không phụ thuộc tên class CSS.

## Ngôn ngữ (Nguyên tắc III)

- `templates/public/**` chỉ dùng chuỗi từ `lang/en.php`; `templates/admin/**` chỉ dùng chuỗi từ `lang/vi.php`.
- Ngoại lệ duy nhất được phép: `templates/public/partials/preview-banner.php` (dải "Bản xem trước", tiếng Việt) — chỉ
  render khi người quản trị đã đăng nhập dùng chức năng Xem trước; khách không bao giờ thấy.
