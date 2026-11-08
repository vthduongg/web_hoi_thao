# Research: Website hội thảo khoa học quốc tế kèm trang quản trị nội dung

**Feature**: `001-conference-website-cms` | **Date**: 2026-11-08

Ràng buộc từ người dùng: **PHP**, **SQL Server**, giao diện **dễ chỉnh sửa cho người mới**.
Ràng buộc từ constitution v2.0.0: code dễ đọc (I), test mọi chức năng (II), ngôn ngữ giao diện theo đối
tượng (III), đơn giản (IV), tối thiểu thư viện (V).

---

## R1. Framework PHP hay PHP thuần?

- **Decision**: PHP thuần (PHP 8.3+) với một lõi nhỏ tự viết: front controller, bảng route dạng mảng,
  lớp `Request`/`Response`, kết nối PDO, session, CSRF, render template PHP. Không dùng framework.
- **Rationale**:
  - Người mới chỉ cần biết PHP + HTML là đọc được toàn bộ luồng: `public/index.php` → `routes.php` →
    controller → template. Không có "phép màu" (service container, facade, ORM, annotation).
  - Mọi phần cần cho đề bài (routing, session, băm mật khẩu `password_hash`, CSRF, upload, GD tạo ảnh thu
    nhỏ) đã có sẵn trong PHP; phần lõi tự viết ước tính < 600 dòng.
  - Đúng Nguyên tắc IV/V: không kéo theo hàng trăm package phụ thuộc.
- **Alternatives considered**:
  - *Laravel*: đầy đủ, tài liệu nhiều, nhưng > 100 package phụ thuộc, nhiều khái niệm ẩn (Eloquent, facade,
    container), hỗ trợ SQL Server có nhưng ít được dùng; nặng so với website ~20 trang.
  - *Slim / micro-framework*: nhẹ hơn nhưng vẫn thêm PSR-7, DI container; lợi ích nhỏ so với 600 dòng tự viết.

## R2. Kết nối SQL Server

- **Decision**: PDO với driver `pdo_sqlsrv` (Microsoft Drivers for PHP for SQL Server). Viết SQL trực tiếp
  bằng prepared statement trong các lớp `*Repository`. SQL Server 2019 trở lên (bản Express dùng được cho
  môi trường phát triển và site nhỏ).
- **Rationale**: Driver chính thức của Microsoft; là PHP extension nên không tính là thư viện composer.
  SQL viết tay dễ đọc, dễ debug, người mới học được ngay.
- **Alternatives considered**: Doctrine DBAL/ORM (thêm phụ thuộc, khái niệm mapping); `sqlsrv_*` API
  thuần (không thống nhất với PDO, khó viết test).
- **Ghi chú kỹ thuật**: dùng `NVARCHAR` cho mọi cột chữ (tiếng Việt có dấu), `DATETIME2` lưu giờ UTC,
  đổi sang Asia/Ho_Chi_Minh khi hiển thị; phân trang bằng `OFFSET … FETCH NEXT …`.

## R3. Môi trường chạy (hosting)

- **Decision**: Windows Server + IIS + PHP (FastCGI) + SQL Server; URL rewrite bằng `web.config` (IIS URL
  Rewrite). Phát triển cục bộ trên Windows bằng `php -S` (server tích hợp của PHP) + SQL Server Express.
- **Rationale**: Đơn vị dùng SQL Server thường có hạ tầng Windows/IIS sẵn; `php -S` giúp người mới chạy thử
  không cần cài IIS.
- **Alternatives considered**: Linux + Nginx + `msodbcsql` (chạy được, nhưng cài driver phức tạp hơn với
  người mới; vẫn được hỗ trợ vì code không phụ thuộc IIS ngoài `web.config`).

## R4. Giao diện dễ chỉnh sửa cho người mới

- **Decision**:
  - Template là **file PHP thuần chứa HTML** (`templates/public/...`, `templates/admin/...`), mỗi file một
    trang hoặc một khối nhỏ (partial). Chỉ dùng `<?= e($bien) ?>`, `if`, `foreach` — không có ngôn ngữ
    template riêng.
  - **CSS thuần, không framework, không bước build**: `assets/css/theme.css` chứa toàn bộ biến màu, font,
    khoảng cách, bo góc (CSS custom properties) — đổi màu thương hiệu chỉ cần sửa file này;
    `public.css` và `admin.css` chia theo khối có comment tiêu đề tiếng Việt.
  - **JavaScript thuần**, mỗi chức năng một file nhỏ (`menu-mobile.js`, `sortable-list.js`, `confirm-delete.js`).
  - Tài liệu `docs/huong-dan-giao-dien.md`: bảng "muốn sửa X → mở file Y".
  - Tên class CSS dễ hiểu theo khối (`.site-header`, `.dates-table__row--upcoming`).
- **Rationale**: Người mới chỉ cần HTML/CSS cơ bản; không phải cài Node, không chạy build; lỗi hiển thị
  lần ra ngay file tương ứng.
- **Alternatives considered**: Twig/Blade (thêm cú pháp phải học, thêm phụ thuộc); Bootstrap/Tailwind (người
  mới phải học hệ class; Tailwind cần build); SPA React/Vue (quá phức tạp, hại tốc độ tải và SEO).

## R5. Trình soạn thảo trực quan

- **Decision**: TinyMCE 7 bản tự host (đặt trong `public/assets/vendor/tinymce/`, `license_key: 'gpl'`), bật
  các plugin: lists, table, link, image, media, code; ảnh/tệp chọn từ Thư viện tệp qua hộp thoại riêng.
- **Rationale**: Giao diện giống Word, quen thuộc với người không chuyên; hỗ trợ bảng, nhúng video, dán
  từ Word. Không cần build. Tự host nên không phụ thuộc dịch vụ ngoài.
- **Alternatives considered**: CKEditor 5 (tương đương, nhưng bản tự host cần build tuỳ biến plugin);
  Quill/Trix (yếu về bảng); tự viết `contenteditable` (không hợp lý về công sức và độ tin cậy).

## R6. Làm sạch HTML từ trình soạn thảo (chống XSS)

- **Decision**: HTML Purifier (`ezyang/htmlpurifier`) lọc nội dung khi lưu; cho phép iframe chỉ từ
  `youtube.com`, `youtube-nocookie.com`, `google.com/maps` (SafeIframe).
- **SVG**: không cho tải lên. HTML Purifier không lọc được SVG, mà SVG chứa `<script>` phục vụ cùng tên miền là
  lỗ hổng XSS. Thư viện tệp chỉ nhận JPG, PNG, WebP, GIF (ảnh) và PDF, DOC, DOCX (tài liệu); kiểm tra bằng `finfo`.
  Thêm lớp phòng thủ: `web.config` gửi `X-Content-Type-Options: nosniff` cho `/uploads`.
- **Rationale**: Nội dung soạn thảo hiển thị cho khách; tự viết bộ lọc HTML là rủi ro bảo mật cao —
  thuộc đúng trường hợp "kém an toàn nếu tự viết" của Nguyên tắc V.
- **Alternatives considered**: `strip_tags` (không lọc thuộc tính `onclick`, `javascript:`); Symfony
  HtmlSanitizer (tốt, nhưng kéo theo thêm package Symfony).

## R7. Gửi email đặt lại mật khẩu

- **Decision**: PHPMailer gửi qua SMTP (cấu hình trong `.env`: host, port, user, mật khẩu, TLS).
- **Rationale**: Hàm `mail()` trên Windows/IIS không hỗ trợ xác thực SMTP/TLS; tự viết giao thức SMTP
  kém an toàn. PHPMailer không có phụ thuộc con.
- **Alternatives considered**: Symfony Mailer (nhiều phụ thuộc hơn); API dịch vụ email (thêm tài khoản bên ngoài).

## R8. Nhập thành viên ban từ Excel/CSV

- **Decision**: CSV đọc bằng `fgetcsv` (có sẵn); `.xlsx` đọc bằng OpenSpout (`openspout/openspout`, đọc dạng
  luồng, chỉ cần extension `zip`, `xmlreader`). Có tệp mẫu `.xlsx` và `.csv` để tải về.
- **Rationale**: Ban tổ chức thường có danh sách trong Excel; đọc `.xlsx` thủ công (zip + XML, shared
  strings, kiểu ngày) dễ sai. OpenSpout nhẹ hơn nhiều so với PhpSpreadsheet.
- **Alternatives considered**: PhpSpreadsheet (nặng, nhiều phụ thuộc, tốn bộ nhớ); chỉ hỗ trợ CSV (không
  đáp ứng spec; người dùng hay gặp lỗi mã hoá khi "Save as CSV" tiếng Việt).

## R9. Sắp xếp kéo thả (menu, mốc, thành viên…)

- **Decision**: HTML5 Drag and Drop thuần trong `sortable-list.js` (~80 dòng), **kèm nút ▲/▼** cho bàn phím
  và màn hình cảm ứng; thứ tự gửi lên server dưới dạng danh sách id.
- **Rationale**: Không cần thư viện; nút ▲/▼ đáp ứng yêu cầu điều hướng bằng bàn phím (FR-042) và máy tính bảng.
- **Alternatives considered**: SortableJS (tốt cho cảm ứng, nhưng nút ▲/▼ đã giải quyết; thêm khi thực sự cần).

## R10. Hẹn giờ xuất bản, dọn thùng rác, sao lưu

- **Decision**:
  - **Hẹn giờ xuất bản**: không cần tiến trình nền — trang công khai chỉ hiện khi
    `status = 'published' AND published_at <= thời điểm hiện tại`.
  - **Dọn thùng rác (30 ngày) + sao lưu hằng ngày**: một script `bin/daily.php` chạy lúc 02:00 bằng Windows
    Task Scheduler (hoặc cron): xoá vĩnh viễn mục quá hạn, chạy `BACKUP DATABASE` ra tệp `.bak` có ngày,
    nén thư mục `uploads/` thành `.zip`, giữ 30 bản mới nhất.
- **Điều kiện vận hành sao lưu**: `BACKUP DATABASE` ghi tệp lên đĩa **của máy chủ SQL Server**, không phải
  máy chạy web. Vì vậy `BACKUP_DIR` là đường dẫn nhìn từ máy SQL Server (nếu hai máy tách nhau, dùng thư mục chia
  sẻ UNC `\\server\backup` mà tài khoản dịch vụ SQL Server ghi được và máy web đọc được để dọn bản cũ); tài khoản
  CSDL của ứng dụng cần vai trò `db_backupoperator`. Ghi rõ trong `.env.example` và `docs/van-hanh.md`.
- **Rationale**: Đơn giản nhất đáp ứng FR-025, FR-039, FR-043; một script, một lịch chạy.
- **Alternatives considered**: hàng đợi job (quá phức tạp); SQL Server Agent (không có trong bản Express).

## R11. Thùng rác & xoá an toàn

- **Decision**: Xoá mềm bằng cột `deleted_at`, `deleted_by` trên các bảng nội dung; truy vấn công khai và
  danh sách quản trị luôn lọc `deleted_at IS NULL`. Màn hình Thùng rác gộp các bảng có `deleted_at`.
- **Rationale**: Khôi phục = đặt lại `NULL`, giữ nguyên quan hệ và thứ tự; không cần sao chép dữ liệu.
- **Alternatives considered**: bảng `trash` lưu JSON bản ghi (khó khôi phục quan hệ, khó test).

## R12. Theo dõi tệp đang được dùng

- **Decision**: Khi xoá tệp, tìm nơi dùng **tại thời điểm xoá**: các cột khoá ngoại tới `media_files` (logo,
  ảnh diễn giả, ảnh slider…) và tìm chuỗi đường dẫn tệp trong `pages.body_html`.
- **Rationale**: Không phải giữ bảng "nơi sử dụng" đồng bộ; số lượng trang nhỏ nên truy vấn nhanh.
- **Alternatives considered**: bảng `media_usages` cập nhật mỗi lần lưu (dễ lệch, nhiều code hơn).

## R13. Sửa đồng thời

- **Decision**: Khoá lạc quan bằng cột `version` (số nguyên) trên `pages`; biểu mẫu gửi kèm `version`; nếu
  khác giá trị trong DB thì không ghi, hiện cảnh báo tiếng Việt và liên kết xem bản mới nhất.
- **Rationale**: Đơn giản, test được; kết hợp lịch sử phiên bản nên không mất dữ liệu.

## R14. Ngôn ngữ giao diện (Nguyên tắc III)

- **Decision**: Hai file chuỗi: `lang/en.php` (giao diện công khai) và `lang/vi.php` (giao diện quản trị), hàm
  `t('key')`. Ngôn ngữ công khai lấy từ `conferences.public_language` (hiện chỉ có `en`). Định dạng ngày:
  công khai `j F Y` (ví dụ `15 March 2026`), quản trị `d/m/Y H:i`; múi giờ Asia/Ho_Chi_Minh.
- **Rationale**: Gom chuỗi một nơi, dễ sửa lời văn mà không đụng logic; thêm ngôn ngữ công khai sau này
  chỉ cần thêm một file.

## R15. Bảo mật đăng nhập

- **Decision**: `password_hash`/`password_verify` (bcrypt mặc định của PHP); khoá 15 phút sau 5 lần sai
  (cột `failed_login_count`, `locked_until`); session cookie `HttpOnly`, `Secure`, `SameSite=Lax`, đổi
  session id khi đăng nhập; hết phiên sau 30 phút không hoạt động; token đặt lại mật khẩu ngẫu nhiên 32 byte,
  lưu dạng SHA-256, dùng một lần, hết hạn 60 phút; token CSRF cho mọi biểu mẫu POST; mỗi lần đổi mật khẩu
  hoặc khoá tài khoản tăng `session_epoch` để vô hiệu các phiên cũ.
- **Rationale**: Đều có sẵn trong PHP, đáp ứng FR-016–FR-020.

## R16. Kiểm thử (Nguyên tắc II)

- **Decision**: PHPUnit (chỉ ở môi trường phát triển).
  - `tests/Unit`: hàm thuần (tính mốc sắp tới, định dạng ngày, kiểm tra slug, đọc tệp nhập, lọc HTML…).
  - `tests/Integration`: repository/service chạy trên CSDL test SQL Server riêng (`conference_test`), mỗi test
    chạy trong transaction rồi rollback.
  - `tests/Http`: gọi thẳng `App::handle(Request)` với request giả, kiểm tra mã trạng thái, chuyển hướng,
    nội dung HTML — không cần trình duyệt hay HTTP client.
  - `tests/Integration/Cli`: chạy các script `bin/*.php` trên CSDL test (migrate chạy 2 lần không lỗi, seed chạy
    2 lần không tạo trùng, create-admin, daily).
  - `tests/e2e`: **Playwright** (`@playwright/test`, chỉ ở môi trường phát triển) kiểm thử mọi chức năng
    JavaScript trong trình duyệt thật (Chromium): menu di động, slider, copy link, hộp xác nhận xoá, kéo thả và
    nút ▲/▼, hộp chọn tệp, trình soạn thảo. Playwright tự khởi động `php -S` với `APP_ENV=testing` và CSDL test
    đã seed.
  - Một lệnh: `composer test` = PHPUnit rồi `npx playwright test`; `composer test:php` để chạy nhanh phần PHP.
- **Rationale**: Nguyên tắc II yêu cầu mọi chức năng người dùng thấy được có test tự động — kể cả phần chạy
  bằng JavaScript; chỉ trình duyệt thật mới kiểm được kéo thả, focus bàn phím, clipboard.
- **Alternatives considered**: Pest (thêm lớp cú pháp); chỉ kiểm tra tay JS (vi phạm Nguyên tắc II);
  `node --test` + jsdom (jsdom không mô phỏng kéo thả/clipboard đáng tin, vẫn thêm phụ thuộc); Selenium (cài
  driver phức tạp hơn). Node.js chỉ cần trên máy phát triển/CI, **không** cần trên máy chủ chạy website.

## R17. Hiệu năng (< 3 giây trên 4G)

- **Decision**: Render phía server, trang công khai không cần JS để hiển thị; CSS một file (~30 KB); ảnh tải lên
  được tạo bản thu nhỏ 400 px và 1200 px bằng GD, template dùng `srcset` + `loading="lazy"`; header
  `Cache-Control` dài cho `/assets` và `/uploads`.
- **Rationale**: Đáp ứng SC-002 mà không cần lớp cache riêng; nếu cần sau này có thể bật output cache của IIS.

---

## Tổng hợp phụ thuộc (Nguyên tắc V)

| Thành phần | Loại | Lý do không tự viết / không dùng chuẩn |
|---|---|---|
| `pdo_sqlsrv`, `gd`, `mbstring`, `fileinfo`, `zip`, `xmlreader` | PHP extension | Driver/khả năng nền tảng, không có thay thế thuần PHP |
| `ezyang/htmlpurifier` | composer | Lọc XSS tự viết kém an toàn |
| `phpmailer/phpmailer` | composer | `mail()` không hỗ trợ SMTP có xác thực/TLS |
| `openspout/openspout` | composer | Đọc `.xlsx` thủ công dễ sai |
| TinyMCE 7 | JS tự host | Trình soạn thảo trực quan không thể tự viết hợp lý |
| `phpunit/phpunit` | composer (dev) | Framework test (được constitution liệt kê là hợp lệ) |
| `friendsofphp/php-cs-fixer` | composer (dev) | Format tự động theo PSR-12 — một bộ quy tắc duy nhất (Nguyên tắc I) |
| `@playwright/test` | npm (dev) | Framework test trình duyệt cho phần JavaScript (Nguyên tắc II); không chạy trên máy chủ |

Tất cả phiên bản được khoá trong `composer.lock` và `package-lock.json`; TinyMCE (kèm gói ngôn ngữ `vi`) ghi rõ
phiên bản trong thư mục vendor.

## R19. Ba bổ sung nhỏ ngoài plan ban đầu (ghi nhận sau /speckit-converge)

- **Header `X-Frame-Options: SAMEORIGIN`** (`src/Core/App.php`): chặn trang web khác nhúng website/trang quản trị vào
  iframe để lừa bấm (clickjacking). Một dòng, không phụ thuộc thư viện; bổ sung cho R18.
- **Tự tạo CSDL nếu chưa có** (`Database::createIfMissing`, dùng bởi `bin/migrate.php` và test): người mới không phải
  tạo `conference` / `conference_test` bằng tay trước khi chạy migrate (đúng mục tiêu "dễ cho người mới").
- **Biến `APP_DEBUG_ERRORS=1`** (`src/Core/App.php`): chỉ có tác dụng khi `APP_ENV=testing`; cho phép ném lại lỗi
  gốc thay vì trang 500 để dễ tìm nguyên nhân khi một test hỏng. Không ảnh hưởng môi trường chạy thật.

## R18. Chính sách bảo mật nội dung (CSP)

- **Decision**: Hai chính sách riêng, gửi bằng header từ `App`:
  - Công khai: `default-src 'self'; img-src 'self' data:; frame-src https://www.youtube-nocookie.com https://www.youtube.com https://www.google.com; script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'`.
  - Quản trị (`/admin`): như trên nhưng `style-src 'self' 'unsafe-inline'` (TinyMCE chèn style inline vào nội dung
    soạn thảo) và `img-src 'self' data: blob:`.
- **Rationale**: Trang công khai chặt nhất có thể; nới đúng một chỗ TinyMCE cần, chỉ trong khu vực đã đăng nhập.
