# Quickstart: Chạy thử và kiểm tra website hội thảo

Tài liệu này dùng để **kiểm chứng** tính năng chạy đúng từ đầu tới cuối. Chi tiết dữ liệu: [data-model.md](./data-model.md);
đường dẫn: [contracts/public-routes.md](./contracts/public-routes.md), [contracts/admin-routes.md](./contracts/admin-routes.md).

## 1. Chuẩn bị

- PHP 8.3+ với extension: `pdo_sqlsrv`, `gd`, `mbstring`, `fileinfo`, `zip`, `xmlreader`, `openssl`.
- Microsoft ODBC Driver 18 for SQL Server; SQL Server 2019+ (Express được).
- Composer 2.
- Node.js 20+ (chỉ để chạy test trình duyệt Playwright; máy chủ chạy website không cần Node).
- Hai CSDL trống: `conference` (chạy thử) và `conference_test` (chỉ cho test, bị xoá dữ liệu khi chạy test).
- Tài khoản CSDL có vai trò `db_backupoperator` nếu muốn chạy sao lưu; `BACKUP_DIR` là đường dẫn nhìn từ máy SQL Server.

## 2. Cài đặt

```powershell
composer install
npm ci                               # cài Playwright (chỉ máy phát triển)
npx playwright install chromium
Copy-Item .env.example .env          # điền DB_HOST, DB_NAME, DB_USER, DB_PASS, SMTP_*, APP_URL
php bin/migrate.php                  # tạo bảng
php bin/seed.php                     # tạo hội thảo mẫu + các trang mặc định (FR-003) + mốc mẫu
php bin/create-admin.php admin@example.com "Quản trị viên"   # in ra mật khẩu tạm
php -S localhost:8000 -t public public/index.php
```

Mở `http://localhost:8000` (trang công khai) và `http://localhost:8000/admin` (quản trị).

## 3. Chạy toàn bộ test

```powershell
composer test
```

Kỳ vọng: tất cả test pass (unit, integration trên `conference_test`, CLI, HTTP, rồi Playwright trong Chromium).
Chạy nhanh chỉ phần PHP: `composer test:php`.

## 4. Kịch bản kiểm tra tay (theo user story)

| # | Bước | Kết quả mong đợi |
|---|---|---|
| US1 | Mở `/` | Thấy tên, chủ đề, ngày, địa điểm, ngôn ngữ, hai hạn nộp, lối tắt Call for Papers / Paper Submission |
| US1 | Admin → Thông tin chung: xoá link nộp bài; mở `/call-for-papers` | Nút "Submission opening soon" bị vô hiệu |
| US1 | Đặt mốc nộp tóm tắt = `extended`, ngày cũ 01/03, ngày mới 15/03; mở `/important-dates` và `/` | Cả hai trang hiện ~~1 March~~ 15 March + "Extended"; mốc sắp tới được tô nổi bật |
| US2 | Đăng nhập biên tập viên, sửa `paper-submission`, thêm PDF, bấm Xuất bản (bấm giờ) | Xong < 5 phút; tải lại trang công khai thấy nội dung mới |
| US2 | Đặt trang về Bản nháp; mở trang khi chưa đăng nhập | 404 "Page not found"; nút Xem trước trong admin vẫn xem được |
| US2 | Mở cùng một trang ở hai tab, lưu tab 1 rồi lưu tab 2 | Tab 2 bị cảnh báo xung đột, không ghi đè |
| US3 | Nhập tệp mẫu 30 dòng vào "Scientific Committee"; nhập lại tệp có một dòng thiếu họ tên | Lần 1 thêm đủ 30; lần 2 báo "Dòng n: thiếu Họ tên", không thêm dòng nào |
| US3 | Ẩn một nhà tài trợ | Biến mất khỏi `/sponsor` ngay sau khi lưu |
| US4 | Mở `/contact` trên điện thoại (hoặc DevTools 360 px) | Menu thu gọn; không cuộn ngang; bấm email/số điện thoại mở ứng dụng tương ứng |
| US5 | Biên tập viên mở thẳng `/admin/users`, `/admin/activity` | 403 |
| US5 | Nhập sai mật khẩu 5 lần | Lần 6 báo khoá 15 phút |
| US6 | Kéo thả mục menu "Program" lên đầu (và thử bằng nút ▲ với bàn phím) | Menu công khai đổi thứ tự |
| US7 | Xoá ảnh đang dùng ở Travel Info | Cảnh báo liệt kê nơi dùng; sau xác nhận vào thùng rác; khôi phục được |
| US8 | Sao chép hội thảo năm hiện tại sang năm sau, đặt bản mới làm hiện hành | `/` hiện hội thảo mới (không có diễn giả, chương trình); `/{năm cũ}/` hiện bản cũ + dải lưu trữ; `/past-conferences` liệt kê năm cũ |

## 5. Kiểm tra chất lượng

- **Tiếp cận**: dùng bàn phím (Tab/Enter/Esc) đi hết menu và một trang nội dung; chạy Lighthouse Accessibility ≥ 90.
- **Tốc độ**: Lighthouse chế độ di động (giả lập 4G) cho `/` và `/important-dates`: hiển thị nội dung chính < 3 giây.
- **Sao lưu**: chạy `php bin/daily.php`; kiểm tra có tệp `.bak` và `.zip` mới trong thư mục sao lưu; khôi phục `.bak` vào
  CSDL tạm thành công.
- **Ngôn ngữ**: trang công khai không có chữ tiếng Việt cố định; trang quản trị không có chữ tiếng Anh cố định
  (trừ nội dung do người dùng nhập).
