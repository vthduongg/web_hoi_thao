# Website hội thảo khoa học quốc tế

Website cho hội thảo khoa học quốc tế hằng năm: **trang công khai bằng tiếng Anh** (ngôn ngữ của hội thảo) và
**trang quản trị bằng tiếng Việt**, để ban tổ chức tự cập nhật mọi nội dung mà không cần lập trình viên.

- PHP 8.3, không dùng framework; SQL Server 2019+ (Express được).
- Giao diện: template PHP thuần, CSS thuần, JavaScript thuần. Không có bước build.
- Thư viện: HTML Purifier (lọc HTML), PHPMailer (email), OpenSpout (đọc Excel), TinyMCE (trình soạn thảo, tự host).

## Cài đặt trên máy phát triển

Cần có:
- PHP 8.3+ với extension `pdo_sqlsrv`, `gd`, `mbstring`, `fileinfo`, `zip`, `xmlreader`, `openssl`
- Microsoft ODBC Driver 17 hoặc 18 for SQL Server, và SQL Server 2019+
- Composer 2
- Node.js 20+ (chỉ để chạy test trình duyệt; máy chủ thật không cần)
- Hai CSDL trống: `conference` (chạy thử) và `conference_test` (chỉ cho test, dữ liệu bị xoá khi chạy test)

```powershell
composer install
npm ci
npx playwright install chromium
Copy-Item .env.example .env          # điền DB_HOST, DB_NAME, DB_USER, DB_PASS, SMTP_*, APP_URL, BACKUP_DIR
php bin/migrate.php                  # tạo bảng
php bin/seed.php                     # tạo hội thảo mẫu + các trang mặc định + mốc mẫu
php bin/create-admin.php admin@example.com "Quản trị viên"   # in ra mật khẩu tạm
php -S localhost:8000 -t public public/index.php
```

Mở <http://localhost:8000> (trang công khai) và <http://localhost:8000/admin> (quản trị).

## Chạy test

```powershell
composer test        # toàn bộ: PHPUnit (unit, integration, CLI, HTTP) rồi Playwright (Chromium, desktop + điện thoại)
composer test:php    # chỉ phần PHP, nhanh
composer format      # định dạng code theo PSR-12
```

## Triển khai lên máy chủ (IIS)

1. Cài PHP 8.3 (NTS) qua FastCGI, extension như trên, ODBC Driver for SQL Server.
2. Trỏ website IIS vào thư mục `public/`. File `public/web.config` đã có sẵn quy tắc chuyển URL về `index.php`
   (cần module **URL Rewrite**) và chặn chạy script trong `public/uploads/`.
3. Chạy `composer install --no-dev`, tạo `.env` với `APP_ENV=production` và `APP_URL=https://…`, rồi
   `php bin/migrate.php`, `php bin/seed.php`, `php bin/create-admin.php …`.
4. Cấp quyền ghi cho tài khoản chạy website vào `public/uploads/` và thư mục tạm của hệ thống (bộ nhớ đệm của HTML Purifier).
5. Đặt lịch chạy `bin/daily.php` lúc 02:00 (dọn thùng rác + sao lưu): xem [docs/van-hanh.md](docs/van-hanh.md).

## Tài liệu

| Dành cho | Tài liệu |
|---|---|
| Ban tổ chức (người cập nhật nội dung) | [docs/huong-dan-quan-tri.md](docs/huong-dan-quan-tri.md) |
| Người chỉnh giao diện (màu, font, bố cục) | [docs/huong-dan-giao-dien.md](docs/huong-dan-giao-dien.md) |
| Người quản lý máy chủ (sao lưu, khôi phục, bảo mật) | [docs/van-hanh.md](docs/van-hanh.md) |
| Lập trình viên | [specs/001-conference-website-cms/](specs/001-conference-website-cms/) (spec, plan, data-model, contracts, tasks) |

## Cấu trúc thư mục

```text
bin/         lệnh dòng lệnh: migrate, seed, create-admin, daily
database/    migrations/: các file SQL tạo bảng, chạy theo thứ tự số
lang/        chữ cố định: en.php (công khai), vi.php (quản trị)
public/      gốc website: index.php, assets/ (css, js, tinymce), uploads/
src/         code PHP: Core/, Controllers/, Services/, Repositories/, Models/
templates/   template: public/ và admin/
tests/       Unit/, Integration/, Http/, e2e/ (Playwright)
docs/        hướng dẫn sử dụng và vận hành
```
