---

description: "Task list for 001-conference-website-cms"
---

# Tasks: Website hội thảo khoa học quốc tế kèm trang quản trị nội dung

**Input**: Design documents from `specs/001-conference-website-cms/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: BẮT BUỘC — constitution Nguyên tắc II yêu cầu mỗi chức năng có test luồng chính + một test lỗi/biên.
Trong mỗi phase, viết test trước, chạy thấy FAIL, rồi mới cài đặt.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1–US8 theo spec.md)

## Path Conventions

Một project web render phía server tại gốc repo: `public/`, `src/`, `templates/`, `lang/`, `database/migrations/`,
`bin/`, `tests/` (xem plan.md › Project Structure).

**Quy ước áp dụng cho MỌI task** (không nhắc lại từng task):

- PHP `declare(strict_types=1);`, PSR-12, namespace `App\` ánh xạ `src/`.
- Cột chữ `NVARCHAR`; thời điểm `DATETIME2` lưu UTC; mọi bảng có `created_at`, `updated_at`.
- Bảng có xoá mềm thêm `deleted_at DATETIME2 NULL`, `deleted_by INT NULL`; truy vấn thường lọc `deleted_at IS NULL`.
- Bảng có Hiện/Ẩn thêm `is_visible BIT NOT NULL DEFAULT 1`; truy vấn công khai lọc `is_visible = 1`.
- Mọi biểu mẫu quản trị kiểm tra hợp lệ phía server, lỗi tiếng Việt lấy từ `lang/vi.php`; mọi chuỗi công khai lấy từ `lang/en.php`.
- Template chỉ hiển thị, in giá trị bằng `e()` (xem contracts/ui-theme.md); CSS chỉ dùng biến trong `theme.css`.
- Mọi thao tác ghi trong quản trị gọi `ActivityLogger`; mọi nút xoá có hộp xác nhận và chỉ xoá mềm.
- Mọi file JS có test Playwright trong `tests/e2e/`; mọi script `bin/*.php` có test trong `tests/Integration/Cli/`.
- Mọi đoạn code cần "thời điểm hiện tại" dùng `Clock::now()` (không gọi `time()`/`new DateTime()` trực tiếp).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Khởi tạo project, công cụ, cấu trúc thư mục

- [X] T001 Tạo cây thư mục theo plan.md: `public/assets/{css,js,vendor}`, `public/uploads/`, `src/{Core,Repositories,Services,Controllers/Public,Controllers/Admin,Models}`, `templates/{public/partials,public/modules,admin}`, `lang/`, `database/migrations/`, `bin/`, `docs/`, `tests/{Unit,Integration,Http}`
- [X] T002 Tạo `composer.json`: PHP `>=8.3`, autoload PSR-4 `App\\` → `src/`, `files` → `src/Core/helpers.php`; require `ezyang/htmlpurifier`, `phpmailer/phpmailer`, `openspout/openspout`; require-dev `phpunit/phpunit`, `friendsofphp/php-cs-fixer`; scripts `test:php` = `phpunit`, `test:e2e` = `npx playwright test`, `test` = `@test:php` rồi `@test:e2e` (một lệnh chạy toàn bộ), `format` = `php-cs-fixer fix`; yêu cầu ext `pdo_sqlsrv`, `gd`, `mbstring`, `fileinfo`, `zip`, `xmlreader`; chạy `composer install` để sinh `composer.lock`
- [X] T003 [P] Tạo `.php-cs-fixer.dist.php` (rule set `@PSR12`, phạm vi `src/`, `tests/`, `bin/`, `templates/`)
- [X] T004 [P] Tạo `.env.example` với `APP_ENV`, `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_TEST_NAME`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_FROM`, `BACKUP_DIR` (comment tiếng Việt cho từng biến; với `BACKUP_DIR` ghi rõ: "đường dẫn nhìn từ máy SQL Server; nếu SQL Server ở máy khác dùng thư mục chia sẻ UNC; tài khoản CSDL cần vai trò db_backupoperator") và `.gitignore` (`/vendor`, `/node_modules`, `/test-results`, `/playwright-report`, `.env`, `public/uploads/*`, backup)
- [X] T005 [P] Tạo `phpunit.xml`: 3 test suite `Unit`, `Integration`, `Http`; bootstrap `tests/bootstrap.php`
- [X] T006 [P] Tải TinyMCE 7 bản tự host vào `public/assets/vendor/tinymce/` kèm gói ngôn ngữ tiếng Việt `public/assets/vendor/tinymce/langs/vi.js`; ghi số phiên bản của cả hai vào `public/assets/vendor/README.md`
- [X] T007 [P] Tạo `public/web.config`: rewrite mọi request không phải tệp có thật về `index.php`; chặn thực thi `.php` trong `uploads/`; header `Cache-Control: max-age=2592000` cho `/assets` và `/uploads`; header `X-Content-Type-Options: nosniff` cho `/uploads`
- [X] T008 [P] Tạo `package.json` (chỉ devDependency `@playwright/test` với phiên bản cố định chính xác, script `test` = `playwright test`), chạy `npm install` để sinh `package-lock.json`; tạo `playwright.config.js`: thư mục `tests/e2e`, Chromium, hai project `desktop` (1280×800) và `mobile` (360×740), `webServer` chạy `php bin/migrate.php --test && php bin/seed.php --test --reset && php -S 127.0.0.1:8123 -t public public/index.php` với `APP_ENV=testing`; tạo `tests/e2e/helpers.js` (`loginAs(page, 'admin'|'editor')` dùng tài khoản test do seed tạo)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Lõi tự viết, CSDL nền, test harness, layout — mọi user story phụ thuộc phase này

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests cho nền tảng

- [X] T009 Tạo `tests/bootstrap.php` và `tests/Support/DatabaseTestCase.php` (kết nối `DB_TEST_NAME`, chạy migrations nếu thiếu, mỗi test bọc transaction rồi rollback) và `tests/Support/HttpTestCase.php` (helper `get()`, `post()` tạo `Request` giả gọi `App::handle()`, `loginAs($role)`, tự gắn token CSRF, assert mã trạng thái/redirect/chuỗi HTML)
- [X] T010 [P] Unit test `Router` (khớp `/{slug}`, `/{year}/{slug}` với `{year}`=4 chữ số và `{slug}`=`[a-z0-9-]+`, trả 404 khi không khớp, 405 sai phương thức) trong `tests/Unit/Core/RouterTest.php`
- [X] T011 [P] Unit test `Csrf`, `Validator` (required, max length, email, url `http(s)://`, date, enum) và helper `e()`, `format_date()` (công khai `j F Y` → "15 March 2026", quản trị `d/m/Y H:i`, đổi UTC → Asia/Ho_Chi_Minh) trong `tests/Unit/Core/CsrfTest.php`, `ValidatorTest.php`, `HelpersTest.php`
- [X] T012 [P] Unit test `Lang` (`t()` lấy đúng file `en`/`vi`, khoá thiếu trả về chính khoá) trong `tests/Unit/Core/LangTest.php`
- [X] T013 [P] Unit test `HtmlSanitizer` (giữ `<table>`, `<a href>`, `<img>`; xoá `<script>`, `onclick`, `javascript:`; giữ iframe chỉ từ `youtube.com`, `youtube-nocookie.com`, `google.com/maps`) trong `tests/Unit/Services/HtmlSanitizerTest.php`
- [X] T014 [P] Integration test `Database` (query/first/execute/transaction rollback, NVARCHAR giữ tiếng Việt có dấu) và `ActivityLogger` trong `tests/Integration/DatabaseTest.php`, `tests/Integration/ActivityLoggerTest.php`
- [X] T015 [P] Unit test `Clock` (mặc định trả giờ thật UTC; `Clock::freeze('2026-03-10 08:00:00')` cố định giá trị; `Clock::reset()` trả lại giờ thật) trong `tests/Unit/Core/ClockTest.php`
- [X] T016 [P] Unit test `SortOrderService` (nhận danh sách id theo thứ tự mới, chỉ chấp nhận id thuộc đúng hội thảo/nhóm, ghi `sort_order` 10, 20, 30…) trong `tests/Unit/Services/SortOrderServiceTest.php`
- [X] T017 [P] Integration test CLI `bin/migrate.php` trong `tests/Integration/Cli/MigrateTest.php`: chạy hai lần liên tiếp không lỗi và không chạy lại tệp đã áp dụng; áp dụng theo thứ tự tên tệp; ghi `schema_migrations`; tệp SQL lỗi → rollback tệp đó, dừng, exit code ≠ 0
- [X] T018 [P] Integration test CLI `bin/seed.php` trong `tests/Integration/Cli/SeedTest.php`: chạy hai lần số bản ghi mọi bảng không đổi; đúng một hội thảo `is_current = 1`; với `--test` tạo tài khoản `admin@test.local` (admin) và `editor@test.local` (editor); `--reset` xoá sạch dữ liệu trước khi seed (các phase sau bổ sung assert cho dữ liệu mẫu của mình vào cùng file)
- [X] T019 [P] HTTP test trang lỗi và header công khai trong `tests/Http/ErrorPagesTest.php`: đường dẫn không tồn tại → 404 dùng `templates/public/404.php` tiếng Anh, `<html lang="en">`; ngoại lệ trong controller → 500 tiếng Anh, không lộ thông tin lỗi; mọi phản hồi công khai có header CSP công khai đúng research.md R18, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`

### Lõi

- [X] T020 Viết `src/bootstrap.php`: đọc `.env` (tự viết parser dòng `KEY=VALUE`), đặt `date_default_timezone_set('UTC')`, tạo `Database`, trả về `App`
- [X] T021 [P] Viết `src/Core/Request.php` (method, path, query, post, files, cookies, ip) và `src/Core/Response.php` (status, headers, body; `redirect()`, `html()`, `notFound()`)
- [X] T022 [P] Viết `src/Core/Router.php`: đăng ký route `[method, pattern, handler, permission]`, khớp tham số `{name}`
- [X] T023 [P] Viết `src/Core/Database.php` bọc PDO `sqlsrv:Server=…;Database=…` với `ERRMODE_EXCEPTION`, `query()`, `first()`, `execute()`, `lastInsertId()`, `transaction(callable)`
- [X] T024 [P] Viết `src/Core/Session.php` (cookie `HttpOnly`, `Secure` khi https, `SameSite=Lax`, `regenerate()`), `src/Core/Csrf.php`, `src/Core/Flash.php`
- [X] T025 [P] Viết `src/Core/Validator.php` (quy tắc như T011, thông báo lỗi lấy từ `lang/vi.php`)
- [X] T026 [P] Viết `src/Core/Lang.php`, `src/Core/View.php` (render `templates/*.php` với mảng biến, `partial()`), `src/Core/helpers.php` (`e()`, `t()`, `format_date()`, `partial()`, `url()`, `asset()`)
- [X] T027 [P] Viết `src/Core/Clock.php` (`now(): DateTimeImmutable` UTC, `freeze()`, `reset()`)
- [X] T028 Viết `src/Core/App.php`: `handle(Request): Response` — khớp route, kiểm CSRF cho mọi POST (sai → 419), kiểm đăng nhập/quyền theo `permission` của route (sẽ nối với `Auth` ở US5), bắt ngoại lệ → 500; chọn trang lỗi theo khu vực: đường dẫn bắt đầu `/admin` → `templates/admin/error.php` (tiếng Việt), còn lại → `templates/public/404.php` / `500.php` (ngôn ngữ công khai); gắn header CSP theo research.md R18 (chính sách công khai và chính sách `/admin` riêng), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`
- [X] T029 [P] Viết `src/Services/HtmlSanitizer.php` bọc HTML Purifier (cấu hình như T013, `Cache.SerializerPath` trong thư mục tạm)
- [X] T030 Viết `public/index.php` (front controller) và `src/routes.php` (khởi đầu rỗng, các phase sau bổ sung)

### CSDL nền

- [X] T031 Viết `bin/migrate.php`: tạo bảng `schema_migrations(filename NVARCHAR(255) PRIMARY KEY, applied_at DATETIME2)`, chạy theo thứ tự tên các tệp `database/migrations/*.sql` chưa chạy, mỗi tệp trong một transaction; hỗ trợ tham số `--test` dùng `DB_TEST_NAME`
- [X] T032 Migration `database/migrations/001_create_conferences.sql` theo data-model.md: `name NVARCHAR(255)` bắt buộc, `theme NVARCHAR(255)`, `year INT` bắt buộc **UNIQUE**, `venue NVARCHAR(500)`, `conference_language NVARCHAR(100)`, `public_language VARCHAR(5) DEFAULT 'en'`, `logo_media_id`/`favicon_media_id INT NULL`, `contact_email NVARCHAR(255)`, `contact_phone NVARCHAR(50)`, `footer_text NVARCHAR(1000)`, `copyright_text NVARCHAR(255)`, `submission_url NVARCHAR(1000) NULL`, `facebook_url`/`linkedin_url`/`x_url`/`youtube_url NVARCHAR(1000) NULL`, `home_intro_html NVARCHAR(MAX)`, `program_file_media_id INT NULL`, `is_current BIT` (unique filtered index `WHERE is_current = 1`), `has_been_current BIT DEFAULT 0`
- [X] T033 [P] Migration `database/migrations/002_create_users.sql`: `users` (`email NVARCHAR(255)` UNIQUE, `full_name NVARCHAR(255)`, `password_hash NVARCHAR(255)`, `role VARCHAR(10)` CHECK IN (`admin`,`editor`), `is_active BIT DEFAULT 1`, `failed_login_count INT DEFAULT 0`, `locked_until DATETIME2 NULL`, `session_epoch INT DEFAULT 0`, `last_login_at DATETIME2 NULL`) và `password_reset_tokens` (`user_id` FK, `token_hash CHAR(64)` UNIQUE, `expires_at DATETIME2`, `used_at DATETIME2 NULL`)
- [X] T034 [P] Migration `database/migrations/003_create_activity_logs.sql`: `user_id INT NULL`, `action VARCHAR(30)`, `entity_type VARCHAR(30)`, `entity_id INT NULL`, `entity_label NVARCHAR(255)`, `conference_id INT NULL`, `ip_address VARCHAR(45)`, `created_at DATETIME2`; index (`created_at`), (`user_id`)
- [X] T035 [P] Migration `database/migrations/004_create_media_files.sql`: `original_name NVARCHAR(255)`, `stored_path NVARCHAR(500)`, `mime_type VARCHAR(100)`, `size_bytes INT`, `kind VARCHAR(10)` CHECK IN (`image`,`document`), `width`/`height INT NULL`, `thumb_small_path`/`thumb_large_path NVARCHAR(500) NULL`, `alt_text NVARCHAR(500) NULL`, `uploaded_by INT`, xoá mềm; thêm FK từ `conferences.logo_media_id`, `favicon_media_id`, `program_file_media_id`
- [X] T036 [P] Viết `src/Models/Conference.php`, `src/Repositories/ConferenceRepository.php` (`current()`, `findByYear()`, `find()`, `update()`) và `src/Services/ActivityLogger.php` (`log(userId, action, entityType, entityId, label, conferenceId)`; chỉ INSERT)
- [X] T037 [P] Viết `src/Services/SortOrderService.php` và `public/assets/js/sortable-list.js` (kéo thả HTML5 + nút ▲/▼ dùng được bằng bàn phím, hỗ trợ tuỳ chọn lồng cấp với nút →/← và giới hạn số cấp qua `data-max-depth`; phần tử điều khiển dùng `data-sortable`, gửi `ids[]`) — dùng chung cho US3, US6
- [X] T038 Viết `bin/seed.php` (khởi đầu): tạo một hội thảo mẫu `is_current = 1`, `has_been_current = 1`; tham số `--test` (dùng `DB_TEST_NAME`, tạo tài khoản `admin@test.local`/`editor@test.local` với mật khẩu cố định `Test@12345` — chỉ khi `--test`), `--reset` (xoá dữ liệu trước khi seed) — các phase sau bổ sung dữ liệu mẫu của mình; chạy lại không tạo trùng

### Layout & giao diện nền

- [X] T039 [P] Tạo `lang/en.php` và `lang/vi.php` (mảng khoá → chuỗi; khoá chung: nút, lỗi kiểm tra, "Page not found", "Information will be updated soon.")
- [X] T040 [P] Tạo `public/assets/css/theme.css` chỉ chứa biến CSS (`--color-primary`, `--color-accent`, `--color-text`, `--color-bg`, `--font-body`, `--font-heading`, thang cỡ chữ, khoảng cách, bo góc, `--content-max-width: 1200px`), màu mặc định đạt tương phản ≥ 4.5:1, comment tiếng Việt cho từng biến
- [X] T041 [P] Tạo `public/assets/css/public.css` (reset, typography, nút, bảng, lưới thẻ, header/footer; breakpoint `600px`, `900px`, `1200px`; mỗi khối có comment tiêu đề tiếng Việt) và `public/assets/css/admin.css` (layout thanh bên + nội dung, biểu mẫu, bảng danh sách, thông báo flash, dùng được trên máy tính bảng)
- [X] T042 Tạo `templates/public/layout.php` (`<html lang>` theo `public_language`, `<title>`, meta description, `og:*`, `twitter:card`, link "Skip to content", favicon), `templates/public/partials/header.php`, `footer.php` (điện thoại `tel:`, email `mailto:`, footer, bản quyền, link "Past Conferences"), `templates/public/404.php`, `templates/public/500.php` (chuỗi từ `lang/en.php`)
- [X] T043 Tạo `templates/admin/layout.php` (`lang="vi"`, thanh bên menu chức năng, thanh trên hiện tên người dùng và hội thảo đang sửa, vùng flash), `templates/admin/error.php` (403/404/419/500 tiếng Việt, `lang="vi"`) và `templates/admin/partials/form-errors.php`, `confirm-delete.php`
- [X] T044 [P] Viết `public/assets/js/confirm-delete.js` (mọi form có `data-confirm` hỏi xác nhận bằng hộp thoại trước khi gửi; test Playwright ở US2)

**Checkpoint**: `composer test` chạy được và pass toàn bộ test nền tảng; `php -S` trả trang 404 thân thiện.

---

## Phase 3: User Story 1 - Tác giả tìm thông tin và đi tới nơi nộp bài (Priority: P1) 🎯 MVP

**Goal**: Khách xem trang chủ, Important Dates, Call for Papers, Paper Submission và bấm nút nộp bài (dữ liệu từ seed).

**Independent Test**: Với dữ liệu seed, mở `/` thấy đủ thông tin chính; từ trang chủ tới biểu mẫu nộp bài ≤ 3 lần bấm; Important Dates hiện gia hạn, TBA, mốc sắp tới đúng.

### Tests for User Story 1 ⚠️

- [ ] T045 [P] [US1] Unit test `UpcomingDateFinder`: chọn mốc hiện, không `tba`, `date_start >= hôm nay (giờ VN)`, sớm nhất, hoà thì `sort_order` nhỏ nhất; tất cả đã qua → null; mốc ẩn không tính; "hôm nay" lấy qua `Clock::freeze()` (kiểm cả ranh giới 23:59 giờ VN = 16:59 UTC), trong `tests/Unit/Services/UpcomingDateFinderTest.php`
- [ ] T046 [P] [US1] Unit test `RelatedPagesFinder`: tối đa 6, chỉ trang công khai, loại trang hiện tại, ưu tiên trang chung thẻ rồi cùng nhóm menu; nhóm menu = mục cấp 1 chứa mục đầu tiên (theo thứ tự menu) trỏ tới trang; trang không có trong menu chỉ xét thẻ; rỗng → mảng rỗng, trong `tests/Unit/Services/RelatedPagesFinderTest.php`
- [ ] T047 [P] [US1] Integration test `PageRepository::findPublicBySlug` (chỉ trả khi `status = 'published' AND published_at <= now AND deleted_at IS NULL`; hẹn giờ tương lai → null; slug cũ trong `slug_redirects` → trả slug mới) trong `tests/Integration/PageRepositoryTest.php`
- [ ] T048 [P] [US1] HTTP test trang chủ `/`: có tên, chủ đề, ngày tổ chức (từ mốc `conference_day`), địa điểm, ngôn ngữ, hai hạn nộp, link tới Call for Papers và Paper Submission, `<html lang="en">`, trong `tests/Http/Public/HomeTest.php`
- [ ] T049 [P] [US1] HTTP test `/{slug}`: trang công khai 200 có `<title>`, meta description, `og:image`, khối "Related pages", nút chia sẻ; bản nháp/ẩn/hẹn giờ/không tồn tại → 404; slug cũ → 301, trong `tests/Http/Public/PageTest.php`
- [ ] T050 [P] [US1] HTTP test module `important_dates`: định dạng "15 March 2026", `extended` hiện `<del>ngày cũ</del>` + "Extended", `tba` hiện "To be announced", đúng một hàng `dates-table__row--upcoming`, trong `tests/Http/Public/ImportantDatesTest.php`
- [ ] T051 [P] [US1] HTTP test nút nộp bài: có `submission_url` → `<a … target="_blank" rel="noopener">Submit your paper`; NULL → "Submission opening soon" với `aria-disabled="true"`, trong `tests/Http/Public/SubmissionButtonTest.php`
- [ ] T052 [P] [US1] Playwright test `tests/e2e/public-navigation.spec.js` cho `menu-mobile.js`, `slider.js`, `copy-link.js`: (mobile 360 px) nút "Menu" mở/đóng menu, `aria-expanded` đổi `false`↔`true`, Esc đóng menu con và trả focus về nút cha; (desktop) Tab vào mục cha mở menu con, Enter/Space mở được; không cuộn ngang ở 360 px (`scrollWidth <= clientWidth`); slider tự chuyển ảnh, nút dừng làm ngưng chuyển, với `reducedMotion: 'reduce'` không tự chuyển; "Copy link" ghi đúng URL trang vào clipboard và hiện "Link copied"

### Implementation for User Story 1

- [ ] T053 [P] [US1] Migration `database/migrations/005_create_important_dates.sql`: `conference_id` FK, `label NVARCHAR(255)` bắt buộc, `status VARCHAR(10)` CHECK IN (`normal`,`extended`,`tba`), `date_start DATE NULL` (bắt buộc trừ khi `tba` — CHECK), `date_end DATE NULL` (≥ `date_start`), `original_date DATE NULL` (bắt buộc khi `extended` — CHECK), `special_role VARCHAR(30) NULL` CHECK IN (`abstract_deadline`,`full_paper_deadline`,`conference_day`) với unique filtered index (`conference_id`, `special_role`) `WHERE special_role IS NOT NULL AND deleted_at IS NULL`, `sort_order INT`, `is_visible`, xoá mềm
- [ ] T054 [P] [US1] Migration `database/migrations/006_create_pages.sql`: `pages` (`conference_id`, `title NVARCHAR(255)` bắt buộc, `slug NVARCHAR(150)`, `body_html NVARCHAR(MAX)`, `module VARCHAR(30)` CHECK IN (`none`,`important_dates`,`committees`,`keynote_speakers`,`program`,`sponsors`,`contacts`), `seo_title NVARCHAR(255) NULL`, `seo_description NVARCHAR(500) NULL`, `featured_media_id INT NULL`, `status VARCHAR(10)` CHECK IN (`draft`,`published`,`hidden`), `published_at DATETIME2 NULL`, `version INT DEFAULT 1`, `created_by`, `updated_by`, xoá mềm; unique filtered index (`conference_id`, `slug`) `WHERE deleted_at IS NULL`), `page_revisions` (`page_id`, `revision_no` UNIQUE theo trang, `title`, `body_html`, `seo_title`, `seo_description`, `featured_media_id`, `module`, `created_by`), `page_tags` (`page_id`, `tag NVARCHAR(50)`, PK (page_id, tag)), `slug_redirects` (`conference_id`, `old_slug NVARCHAR(150)` UNIQUE theo hội thảo, `page_id`)
- [ ] T055 [P] [US1] Migration `database/migrations/007_create_menu_items.sql`: `conference_id`, `parent_id INT NULL` FK tự tham chiếu, `label NVARCHAR(100)` bắt buộc, `page_id INT NULL`, `external_url NVARCHAR(1000) NULL`, CHECK không đồng thời có cả `page_id` và `external_url`, `sort_order`, xoá mềm
- [ ] T056 [P] [US1] Migration `database/migrations/008_create_slides_highlights.sql`: `slides` (`conference_id`, `media_id` bắt buộc, `title NVARCHAR(255)`, `alt_text NVARCHAR(500)` bắt buộc, `link_url NVARCHAR(1000) NULL`, `sort_order`, `is_visible`, xoá mềm) và `highlights` (`conference_id`, `title NVARCHAR(255)`, `body_text NVARCHAR(500)`, `link_url NULL`, `sort_order`, `is_visible`, xoá mềm)
- [ ] T057 [P] [US1] Viết models `src/Models/ImportantDate.php`, `Page.php`, `MenuItem.php`, `Slide.php`, `Highlight.php` (readonly properties)
- [ ] T058 [P] [US1] Viết `src/Repositories/ImportantDateRepository.php` (`visibleForConference()`, `findByRole()`) và `src/Services/UpcomingDateFinder.php`
- [ ] T059 [P] [US1] Viết `src/Repositories/PageRepository.php` phần đọc công khai (`findPublicBySlug()`, `findRedirect()`, `publicPagesForConference()`, `tagsFor()`)
- [ ] T060 [P] [US1] Viết `src/Repositories/MenuRepository.php` (`treeForPublic()` — tối đa 2 cấp, ẩn mục trỏ tới trang không công khai) và `src/Repositories/HomeRepository.php` (slides, highlights đang hiện theo thứ tự)
- [ ] T061 [US1] Viết `src/Services/RelatedPagesFinder.php` (dùng tags + nhóm menu theo định nghĩa ở FR-013)
- [ ] T062 [US1] Viết `src/Services/PublicPageContext.php`: gom dữ liệu chung cho mọi trang công khai (hội thảo, menu, footer, dải lưu trữ nếu không hiện hành) — để controller ngắn gọn
- [ ] T063 [US1] Viết `src/Controllers/Public/HomeController.php` và `templates/public/home.php` (slider, tên, chủ đề, đoạn giới thiệu, hộp thông tin chính, lối tắt, khối nổi bật)
- [ ] T064 [US1] Viết `src/Controllers/Public/PageController.php` (slug → trang hoặc 301/404; chọn template module) và `templates/public/page.php`
- [ ] T065 [P] [US1] Tạo `templates/public/modules/important_dates.php` theo contracts/public-routes.md
- [ ] T066 [P] [US1] Tạo `templates/public/partials/menu.php` (menu con mở bằng chuột/chạm/bàn phím, dự phòng `:focus-within`), `share-buttons.php` (link Facebook, X, LinkedIn, "Copy link"), `related-pages.php`, `submit-button.php`, `slider.php`
- [ ] T067 [P] [US1] Viết `public/assets/js/menu-mobile.js` (nút "Menu" dưới 900 px, `aria-expanded`, Esc đóng menu con) và `public/assets/js/slider.js` (tự chuyển ảnh, có nút dừng, tôn trọng `prefers-reduced-motion`), `public/assets/js/copy-link.js`
- [ ] T068 [US1] Thêm route công khai `GET /`, `GET /{slug}` vào `src/routes.php`
- [ ] T069 [US1] Bổ sung `bin/seed.php`: 8 mốc mẫu (thông báo lần 1, lần 2, hạn nộp tóm tắt `abstract_deadline`, thông báo chấp nhận tóm tắt, hạn nộp toàn văn `full_paper_deadline`, thông báo chấp nhận toàn văn, hạn đăng ký, ngày hội thảo `conference_day`); các trang mặc định FR-003 (`call-for-papers`, `important-dates` module `important_dates`, `scientific-committee`, `organizing-committee` module `committees`, `keynote-speakers`, `program`, `paper-submission`, `sponsor`, `contact`, `travel-info`) với nội dung mẫu tiếng Anh; menu mặc định có nhóm "Conference"; 3 slide mẫu dùng ảnh trong `tests/fixtures/` (chỉ với `--test`); bổ sung assert tương ứng vào `tests/Integration/Cli/SeedTest.php`

**Checkpoint**: Khách xem đầy đủ luồng tìm thông tin và nộp bài; toàn bộ test US1 pass.

---

## Phase 4: User Story 5 - Đăng nhập, tài khoản và phân quyền (Priority: P1)

**Goal**: Đăng nhập an toàn, quản lý tài khoản, phân quyền Quản trị viên / Biên tập viên kiểm tra ở server.

**Independent Test**: Biên tập viên mở thẳng `/admin/users` và `/admin/activity` → 403; sai mật khẩu 5 lần → khoá 15 phút; chưa đăng nhập vào `/admin/*` → chuyển tới đăng nhập.

### Tests for User Story 5 ⚠️

- [ ] T070 [P] [US5] Unit test `LoginThrottle`: lần sai thứ 5 đặt `locked_until = now + 15 phút`; khi đang khoá từ chối kể cả mật khẩu đúng; đăng nhập đúng đặt lại `failed_login_count = 0`, trong `tests/Unit/Services/LoginThrottleTest.php`
- [ ] T071 [P] [US5] Integration test `PasswordResetService`: token 32 byte, lưu SHA-256, hết hạn sau 60 phút, dùng một lần; đặt mật khẩu mới tăng `session_epoch`, trong `tests/Integration/PasswordResetServiceTest.php`
- [ ] T072 [P] [US5] HTTP test đăng nhập/đăng xuất/hết phiên: chưa đăng nhập vào `/admin` → 302 `/admin/login?next=/admin`; sai → thông báo tiếng Việt; không thao tác 30 phút → phải đăng nhập lại; session id đổi sau đăng nhập; `<html lang="vi">`, trong `tests/Http/Admin/LoginTest.php`
- [ ] T073 [P] [US5] HTTP test quên mật khẩu: thông báo giống nhau dù email tồn tại hay không; token hết hạn/đã dùng → lỗi; tự đổi mật khẩu sai mật khẩu hiện tại → 422; mật khẩu < 8 ký tự → 422, trong `tests/Http/Admin/PasswordTest.php`
- [ ] T074 [P] [US5] HTTP test quản lý tài khoản: admin tạo/sửa/khoá/mở khoá; email trùng → 422; không khoá/hạ vai trò quản trị viên cuối cùng; tài khoản bị khoá không đăng nhập được và phiên đang mở bị chấm dứt; editor truy cập `/admin/users`, `/admin/settings`, `/admin/menu`, `/admin/activity`, `/admin/conferences` → 403 (kiểm tra ở server), trong `tests/Http/Admin/UsersTest.php`
- [ ] T075 [P] [US5] HTTP test POST thiếu/sai token CSRF → 419, trong `tests/Http/Admin/CsrfTest.php`
- [ ] T076 [P] [US5] HTTP test trang lỗi khu vực quản trị trong `tests/Http/Admin/ErrorPagesTest.php`: đã đăng nhập mở `/admin/khong-ton-tai` → 404 tiếng Việt, `<html lang="vi">`; 403 và 419 hiện `templates/admin/error.php` tiếng Việt; ngoại lệ trong controller quản trị → 500 tiếng Việt; header CSP `/admin` có `style-src 'self' 'unsafe-inline'` còn trang công khai thì không
- [ ] T077 [P] [US5] Integration test CLI `bin/create-admin.php` trong `tests/Integration/Cli/CreateAdminTest.php`: tạo tài khoản `role = admin`, `is_active = 1`, in mật khẩu tạm 12 ký tự đăng nhập được; email trùng → exit code ≠ 0 kèm thông báo tiếng Việt; thiếu tham số → in hướng dẫn sử dụng, exit code ≠ 0

### Implementation for User Story 5

- [ ] T078 [P] [US5] Viết `src/Models/User.php` và `src/Repositories/UserRepository.php` (`findByEmail()` — email lưu chữ thường, `create()`, `update()`, `countActiveAdmins()`, `incrementEpoch()`)
- [ ] T079 [P] [US5] Viết `src/Services/LoginThrottle.php` (5 lần, 15 phút)
- [ ] T080 [US5] Viết `src/Core/Auth.php`: `attempt()` (`password_verify`, throttle, `regenerate()` session, ghi `login`/`login_failed`), `user()`, `logout()`, kiểm tra hết hạn 30 phút không hoạt động, kiểm tra `session_epoch` và `is_active` mỗi request, `can(permission)` với `A` = admin, `E` = admin + editor
- [ ] T081 [US5] Nối `Auth` vào `src/Core/App.php`: route có permission → chưa đăng nhập 302 tới `/admin/login?next=…`, thiếu quyền 403 "Bạn không có quyền thực hiện thao tác này."
- [ ] T082 [P] [US5] Viết `src/Services/Mailer.php` bọc PHPMailer (SMTP từ `.env`; trong `APP_ENV=testing` ghi thư vào mảng để test đọc) và `src/Services/PasswordResetService.php`
- [ ] T083 [US5] Viết `src/Controllers/Admin/AuthController.php` + templates `templates/admin/auth/login.php`, `forgot-password.php`, `reset-password.php`, `templates/admin/emails/reset-password.php` (tiếng Việt)
- [ ] T084 [US5] Viết `src/Controllers/Admin/ProfileController.php` + `templates/admin/profile/password.php`
- [ ] T085 [US5] Viết `src/Controllers/Admin/UserController.php` + `templates/admin/users/index.php`, `form.php` (họ tên, email, vai trò; nút khoá/mở khoá có xác nhận)
- [ ] T086 [US5] Viết `src/Controllers/Admin/DashboardController.php` bản khởi đầu + `templates/admin/dashboard.php` (lời chào, lối tắt; số liệu thêm ở US2)
- [ ] T087 [US5] Thêm route tài khoản theo contracts/admin-routes.md vào `src/routes.php`
- [ ] T088 [US5] Viết `bin/create-admin.php <email> "<họ tên>"`: tạo quản trị viên, in mật khẩu tạm ngẫu nhiên 12 ký tự

**Checkpoint**: Đăng nhập, phân quyền, tài khoản hoạt động; mọi test US5 pass.

---

## Phase 5: User Story 2 - Ban tổ chức tự cập nhật trang nội dung (Priority: P1)

**Goal**: Soạn trang bằng trình soạn thảo trực quan, trạng thái, hẹn giờ, xem trước, lịch sử phiên bản; xuất bản hiện ngay.

**Independent Test**: Biên tập viên sửa đoạn văn + thêm PDF đính kèm, xuất bản trong < 5 phút; trang công khai phản ánh ngay.

### Tests for User Story 2 ⚠️

- [ ] T089 [P] [US2] Unit test `SlugValidator`: chỉ `[a-z0-9-]`, ≤ 150 ký tự, không dùng từ dành riêng `admin`, `assets`, `uploads`, `past-conferences`, `robots.txt`, `sitemap.xml`, không là 4 chữ số; tự sinh slug từ tiêu đề có dấu tiếng Việt ("Hội thảo" → `hoi-thao`), trong `tests/Unit/Services/SlugValidatorTest.php`
- [ ] T090 [P] [US2] Integration test `PageService`: lưu tạo revision mới; `version` lệch → ném `EditConflict`, không ghi; đổi slug của trang đã xuất bản tạo `slug_redirects`; khôi phục revision tạo revision mới và giữ bản hiện tại trong lịch sử; `body_html` được lọc qua `HtmlSanitizer`, trong `tests/Integration/PageServiceTest.php`
- [ ] T091 [P] [US2] Integration test `MediaUploader`: nhận JPG/PNG/WebP/GIF ≤ 5 MB và PDF/DOC/DOCX ≤ 20 MB; từ chối SVG (kể cả khi đổi đuôi thành `.png`), từ chối loại khác; kiểm tra loại bằng `finfo` (đổi đuôi `.php` → `.jpg` bị từ chối); ảnh 5 MB + 1 byte bị từ chối; tên lưu ngẫu nhiên `uploads/YYYY/MM/…`; tạo thumbnail 400 px và 1200 px (không với GIF động), trong `tests/Integration/MediaUploaderTest.php`
- [ ] T092 [P] [US2] HTTP test quản lý trang: tạo/sửa/xuất bản/ẩn/về nháp bởi editor; slug trùng → 422 tiếng Việt; hẹn giờ tương lai → công khai 404 rồi 200 khi tới giờ (giả lập thời gian qua `Clock`); xem trước bản nháp hiện dải "Bản xem trước"; xung đột phiên bản hiện cảnh báo; danh sách lọc theo trạng thái/thẻ/từ khoá, trong `tests/Http/Admin/PagesTest.php`
- [ ] T093 [P] [US2] HTTP test xoá trang: trang có mục menu trỏ tới → POST xoá lần đầu trả trang xác nhận liệt kê các mục menu đó, chưa xoá; POST kèm `confirmed=1` → chuyển vào thùng rác (`deleted_at` có giá trị), công khai → 404, mục menu tự ẩn khỏi menu công khai; trang không có trong menu xoá ngay sau một lần xác nhận ở giao diện, trong `tests/Http/Admin/PageDeleteTest.php`
- [ ] T094 [P] [US2] HTTP test bảng điều khiển trong `tests/Http/Admin/DashboardTest.php`: editor và admin đều vào được; số trang đã xuất bản/bản nháp đúng; 5 mốc sắp tới (dùng `Clock::freeze`); 10 thay đổi nội dung gần nhất chỉ của hội thảo đang sửa, chỉ gồm `create`/`update`/`delete`/`restore`/`publish`/`unpublish`; HTML không chứa địa chỉ IP và không có dòng `login`/`logout`/`login_failed`/`account_change`
- [ ] T095 [P] [US2] HTTP test hộp chọn tệp trong `tests/Http/Admin/MediaPickerTest.php`: `/admin/media/picker` liệt kê ảnh và tài liệu chưa xoá, tìm theo tên gốc có dấu tiếng Việt, lọc `kind=image`; chưa đăng nhập → 302 tới đăng nhập
- [ ] T096 [P] [US2] Playwright test `tests/e2e/editor.spec.js` cho `editor.js` và `media-picker.js`: editor đăng nhập, mở sửa trang `paper-submission`, TinyMCE tải xong với giao diện tiếng Việt; gõ một đoạn văn, chèn bảng; mở hộp chọn tệp, tìm và chèn ảnh mẫu, chèn liên kết tới PDF mẫu; chèn ảnh thiếu mô tả → hiện nhắc nhập mô tả; Xuất bản → mở trang công khai thấy đoạn văn, bảng, ảnh, link PDF
- [ ] T097 [P] [US2] Playwright test `tests/e2e/confirm-delete.spec.js` cho `confirm-delete.js`: bấm "Xoá" một trang → hiện hộp xác nhận; chọn Huỷ → trang vẫn còn trong danh sách; chọn Đồng ý → trang biến mất khỏi danh sách và có trong Thùng rác

### Implementation for User Story 2

- [ ] T098 [P] [US2] Viết `src/Services/SlugValidator.php`
- [ ] T099 [P] [US2] Viết `src/Services/MediaUploader.php` (GD tạo thumbnail; chỉ nhận JPG/PNG/WebP/GIF và PDF/DOC/DOCX, không nhận SVG; giới hạn "ảnh ≤ 5 MB; tài liệu ≤ 20 MB"), `src/Models/MediaFile.php`, `src/Repositories/MediaRepository.php` (`create()`, `find()`, `search()`)
- [ ] T100 [US2] Bổ sung `src/Repositories/PageRepository.php` phần quản trị (`listForAdmin(status, tag, q)`, `insert()`, `update()` có điều kiện `version`, `saveTags()`, `revisions()`, `softDelete()`, `countByStatus()`)
- [ ] T101 [US2] Viết `src/Services/PageService.php` (lưu + revision + tags + redirect + lọc HTML + ghi nhật ký `create`/`update`/`publish`/`unpublish`/`delete`)
- [ ] T102 [US2] Viết `src/Controllers/Admin/PageController.php` (index, create, edit, preview, publish, hide, draft, revisions, restore, delete — delete kiểm tra mục menu trỏ tới trang và yêu cầu xác nhận lần hai theo contracts/admin-routes.md) theo contracts/admin-routes.md
- [ ] T103 [US2] Tạo `templates/admin/pages/index.php`, `form.php` (tiêu đề, slug tự gợi ý, trình soạn thảo, module, thẻ, SEO, ảnh đại diện, trạng thái, ngày giờ xuất bản `dd/mm/yyyy HH:mm`, cảnh báo xung đột), `revisions.php`, `confirm-delete.php` (liệt kê mục menu bị ảnh hưởng); `templates/public/partials/preview-banner.php` (ngoại lệ ngôn ngữ duy nhất theo contracts/ui-theme.md, chỉ render khi đang Xem trước)
- [ ] T104 [US2] Viết `public/assets/js/editor.js`: khởi tạo TinyMCE (`license_key: 'gpl'`, plugin lists, table, link, image, media, code, ngôn ngữ giao diện tiếng Việt), nút chèn ảnh/tệp mở hộp chọn từ thư viện; nhắc nhập mô tả ảnh khi thiếu `alt`
- [ ] T105 [US2] Viết `src/Controllers/Admin/MediaController.php` phần `upload` và `picker` + `templates/admin/media/picker.php` và `public/assets/js/media-picker.js` (dùng cho trình soạn thảo và các trường ảnh)
- [ ] T106 [US2] Viết `src/Services/DashboardService.php` và hoàn thiện `DashboardController` + `templates/admin/dashboard.php`: số trang đã xuất bản/bản nháp, 5 mốc sắp tới, 10 thay đổi nội dung gần nhất (truy vấn `activity_logs` lọc `action IN ('create','update','delete','restore','publish','unpublish')` và hội thảo đang sửa, chỉ lấy người thực hiện, hành động, `entity_label`, `created_at` — không lấy `ip_address`), lối tắt
- [ ] T107 [US2] Thêm route trang và media (upload, picker) vào `src/routes.php`

**Checkpoint**: MVP hoàn chỉnh (US1 + US5 + US2): khách đọc được, ban tổ chức tự sửa và xuất bản.

---

## Phase 6: User Story 3 - Quản lý dữ liệu có cấu trúc dùng chung (Priority: P2)

**Goal**: Quản lý mốc thời gian, ban + thành viên (kèm nhập Excel/CSV), diễn giả, chương trình, nhà tài trợ, người liên hệ; trang công khai hiển thị các module.

**Independent Test**: Đổi hạn nộp tóm tắt một lần → trang chủ, Important Dates, Call for Papers đều đổi; nhập tệp 30 thành viên đúng thứ tự; tệp lỗi không thêm dòng nào.

### Tests for User Story 3 ⚠️

- [ ] T108 [P] [US3] Unit test `CommitteeImporter` theo contracts/import-format.md: nhận `.xlsx` sheet đầu và `.csv` UTF-8 có/không BOM, dấu `,` hoặc `;`; tên cột vi/en không phân biệt hoa thường; bỏ dòng trống; "Họ tên" bắt buộc ≤ 255, "Học hàm học vị" ≤ 100, "Chức vụ đơn vị" ≤ 500, "Vai trò" ≤ 100; > 500 dòng hoặc > 2 MB → lỗi; thiếu cột → "Tệp thiếu cột: Họ tên"; lỗi dòng → "Dòng 7: thiếu Họ tên", trong `tests/Unit/Services/CommitteeImporterTest.php` (tệp mẫu ở `tests/fixtures/`)
- [ ] T109 [P] [US3] HTTP test mốc thời gian: tạo/sửa/sắp xếp/ẩn/xoá; `extended` thiếu `original_date` → 422; `tba` không cần ngày; `date_end` < `date_start` → 422; trùng `special_role` → 422; sửa `abstract_deadline` thì `/` và `/important-dates` cùng đổi, trong `tests/Http/Admin/DatesTest.php`
- [ ] T110 [P] [US3] HTTP test ban + thành viên: tạo nhiều ban, gắn hai ban vào một trang theo thứ tự; công khai hiện mỗi ban một bảng có cột No. / Name / Position & Affiliation / Role; STT đánh lại theo danh sách đang hiện; nhập 30 dòng hợp lệ thêm đúng thứ tự nối tiếp; tệp có lỗi không thêm dòng nào; ban rỗng hiện "Information will be updated soon.", trong `tests/Http/Admin/CommitteesTest.php`
- [ ] T111 [P] [US3] HTTP test diễn giả: `full_name` bắt buộc; thẻ công khai có ảnh, học hàm + họ tên, đơn vị, quốc gia, tiêu đề bài nói, tiểu sử, đúng thứ tự; ẩn → không hiện, trong `tests/Http/Admin/SpeakersTest.php`
- [ ] T112 [P] [US3] HTTP test chương trình: ngày → phiên → mục; `end_time` ≤ `start_time` → 422; mục không có phòng dùng phòng của phiên; không có mục nhưng có tệp → hiện ảnh/PDF; không có cả hai → "Information will be updated soon.", trong `tests/Http/Admin/ProgramTest.php`
- [ ] T113 [P] [US3] HTTP test nhà tài trợ + hạng: `name` và `logo_media_id` bắt buộc; công khai nhóm theo hạng đúng thứ tự, `alt` = tên, link mở tab mới; ẩn → biến mất ngay, trong `tests/Http/Admin/SponsorsTest.php`
- [ ] T114 [P] [US3] HTTP test người liên hệ: email sai định dạng → 422; điện thoại chỉ chấp nhận số, khoảng trắng, `+`, `.`, `-`; công khai có `mailto:` và `tel:`, trong `tests/Http/Admin/ContactsTest.php`
- [ ] T115 [P] [US3] Playwright test `tests/e2e/sortable.spec.js` cho `sortable-list.js` trên trang quản lý mốc thời gian: kéo mốc thứ 3 lên đầu → tải lại trang vẫn đúng thứ tự mới và trang `/important-dates` hiện theo thứ tự đó; chỉ dùng bàn phím (Tab tới nút ▲ của một mốc, Enter) di chuyển được mốc; ở khung 768 px dùng nút ▲/▼ bằng chạm (`tap`) được

### Implementation for User Story 3

- [ ] T116 [P] [US3] Migration `database/migrations/009_create_committees.sql`: `committees` (`conference_id`, `name NVARCHAR(255)` bắt buộc, `sort_order`, `is_visible`, xoá mềm), `page_committees` (`page_id`, `committee_id`, `sort_order`, PK (page_id, committee_id)), `committee_members` (`committee_id`, `academic_title NVARCHAR(100)`, `full_name NVARCHAR(255)` bắt buộc, `position_affiliation NVARCHAR(500)`, `committee_role NVARCHAR(100)`, `photo_media_id INT NULL`, `sort_order`, `is_visible`, xoá mềm)
- [ ] T117 [P] [US3] Migration `database/migrations/010_create_keynote_speakers.sql`: `conference_id`, `photo_media_id INT NULL`, `academic_title NVARCHAR(100)`, `full_name NVARCHAR(255)` bắt buộc, `affiliation NVARCHAR(500)`, `country NVARCHAR(100)`, `talk_title NVARCHAR(500)`, `bio_html NVARCHAR(MAX)`, `sort_order`, `is_visible`, xoá mềm
- [ ] T118 [P] [US3] Migration `database/migrations/011_create_program.sql`: `program_days` (`conference_id`, `day_date DATE` bắt buộc, `title NVARCHAR(255) NULL`, `sort_order`, xoá mềm), `program_sessions` (`program_day_id`, `title NVARCHAR(255)` bắt buộc, `room NVARCHAR(100) NULL`, `sort_order`, xoá mềm), `program_items` (`program_session_id`, `start_time TIME`, `end_time TIME` với CHECK `end_time > start_time`, `content NVARCHAR(1000)` bắt buộc, `presenter NVARCHAR(255) NULL`, `room NVARCHAR(100) NULL`, `sort_order`, `is_visible`, xoá mềm)
- [ ] T119 [P] [US3] Migration `database/migrations/012_create_sponsors.sql`: `sponsor_tiers` (`conference_id`, `name NVARCHAR(100)` bắt buộc, `sort_order`, xoá mềm), `sponsors` (`conference_id`, `sponsor_tier_id` FK, `name NVARCHAR(255)` bắt buộc, `logo_media_id INT` bắt buộc, `website_url NVARCHAR(1000) NULL`, `sort_order`, `is_visible`, xoá mềm)
- [ ] T120 [P] [US3] Migration `database/migrations/013_create_contact_persons.sql`: `conference_id`, `full_name NVARCHAR(255)` bắt buộc, `affiliation NVARCHAR(500)`, `email NVARCHAR(255)`, `phone NVARCHAR(50)`, `sort_order`, `is_visible`, xoá mềm
- [ ] T121 [P] [US3] Viết models `src/Models/Committee.php`, `CommitteeMember.php`, `KeynoteSpeaker.php`, `ProgramDay.php`, `ProgramSession.php`, `ProgramItem.php`, `SponsorTier.php`, `Sponsor.php`, `ContactPerson.php`
- [ ] T122 [P] [US3] Viết `src/Repositories/CommitteeRepository.php` (gồm thành viên và `page_committees`), `SpeakerRepository.php`, `ProgramRepository.php`, `SponsorRepository.php`, `ContactRepository.php`; bổ sung phần ghi cho `ImportantDateRepository.php`
- [ ] T123 [P] [US3] Viết `src/Services/CommitteeImporter.php` (OpenSpout cho `.xlsx`, `fgetcsv` cho `.csv`, tất cả hoặc không trong một transaction, ghi nhật ký `import`) và tệp mẫu tĩnh `public/assets/templates/committee-import-template.xlsx`, `public/assets/templates/committee-import-template.csv` (phục vụ tại `/assets/templates/…` theo contracts/import-format.md)
- [ ] T124 [US3] Viết `src/Controllers/Admin/DateController.php` + `templates/admin/dates/index.php`, `form.php` (trạng thái bình thường / gia hạn kèm ngày cũ / sẽ thông báo sau; vai trò đặc biệt; công tắc Hiện/Ẩn)
- [ ] T125 [US3] Viết `src/Controllers/Admin/CommitteeController.php` (ban, thành viên, gắn ban vào trang, nhập tệp) + `templates/admin/committees/index.php`, `form.php`, `member-form.php`, `import.php` (bảng lỗi theo dòng)
- [ ] T126 [US3] Viết `src/Controllers/Admin/SpeakerController.php` + `templates/admin/speakers/index.php`, `form.php` (tiểu sử dùng trình soạn thảo rút gọn, lọc HTML khi lưu)
- [ ] T127 [US3] Viết `src/Controllers/Admin/ProgramController.php` (ngày, phiên, mục, tải ảnh/PDF thay thế lưu vào `conferences.program_file_media_id`) + `templates/admin/program/index.php`, `day-form.php`, `session-form.php`, `item-form.php`, `file.php`
- [ ] T128 [US3] Viết `src/Controllers/Admin/SponsorController.php` (cả hạng) + `templates/admin/sponsors/index.php`, `form.php`, `tier-form.php`
- [ ] T129 [US3] Viết `src/Controllers/Admin/ContactController.php` + `templates/admin/contacts/index.php`, `form.php`
- [ ] T130 [P] [US3] Tạo `templates/public/modules/committees.php`, `keynote_speakers.php`, `program.php`, `sponsors.php`, `contacts.php` theo contracts/public-routes.md (danh sách rỗng → "Information will be updated soon.")
- [ ] T131 [US3] Nối các module vào `src/Controllers/Public/PageController.php` và thêm route `dates`, `committees`, `speakers`, `program`, `sponsors`, `sponsor-tiers`, `contacts` (mẫu chung list/new/edit/toggle-visibility/reorder/delete) vào `src/routes.php`
- [ ] T132 [US3] Bổ sung `bin/seed.php`: 2 ban mẫu gắn vào `organizing-committee`, 1 ban gắn `scientific-committee`, 2 diễn giả, 3 hạng + 4 nhà tài trợ, 2 người liên hệ; bổ sung assert tương ứng vào `tests/Integration/Cli/SeedTest.php`

**Checkpoint**: Mọi dữ liệu có cấu trúc quản lý được và hiển thị đúng; không lệch ngày giữa các trang.

---

## Phase 7: User Story 4 - Đại biểu chuẩn bị tham dự (Priority: P2)

**Goal**: Travel Info có bản đồ, video, điểm tham quan; Contact và mọi trang dùng tốt trên điện thoại.

**Independent Test**: Trên màn 360 px: menu thu gọn, không cuộn ngang; bấm số điện thoại/email mở ứng dụng tương ứng; bản đồ và video co giãn.

### Tests for User Story 4 ⚠️

- [ ] T133 [P] [US4] Unit test `EmbedConverter`: link YouTube (`watch?v=`, `youtu.be/`) → iframe `youtube-nocookie.com/embed/…`; link/iframe Google Maps embed → giữ; host khác → từ chối, trong `tests/Unit/Services/EmbedConverterTest.php`
- [ ] T134 [P] [US4] HTTP test `/travel-info` có iframe bản đồ + video bọc trong `.embed-responsive` và khối điểm tham quan (ảnh có `alt` + mô tả); `/contact` có `mailto:`/`tel:`; mọi trang có nút "Menu" với `aria-expanded`, trong `tests/Http/Public/TravelContactTest.php`
- [ ] T135 [P] [US4] Playwright test `tests/e2e/travel-info.spec.js`: trong trình soạn thảo, nút "Điểm tham quan" chèn khối ảnh + tiêu đề + mô tả; dán link YouTube và link Google Maps rồi lưu → trang `/travel-info` có iframe `youtube-nocookie.com` và bản đồ; ở 360 px iframe co giãn theo chiều rộng, trang không cuộn ngang; ở `/contact` link `tel:`/`mailto:` có `href` đúng giá trị đã nhập

### Implementation for User Story 4

- [ ] T136 [P] [US4] Viết `src/Services/EmbedConverter.php` và nối vào `PageService` khi lưu (đổi link dán thành iframe hợp lệ trước khi lọc HTML)
- [ ] T137 [P] [US4] Thêm vào `public/assets/css/public.css`: `.embed-responsive` (tỉ lệ 16:9, co giãn), `.attraction-card` (ảnh + mô tả), `.speaker-card`, `.contact-card`, bảng cuộn ngang trong khung riêng khi màn hẹp (trang không cuộn ngang)
- [ ] T138 [US4] Thêm vào `public/assets/js/editor.js` mẫu khối "Điểm tham quan" (ảnh + tiêu đề + mô tả) chèn được bằng một nút trong TinyMCE (`templates` plugin hoặc nút tuỳ biến)
- [ ] T139 [US4] Bổ sung nội dung mẫu `travel-info` trong `bin/seed.php` (vị trí trường, khoảng cách tới sân bay/trung tâm Hà Nội, phương tiện, bản đồ, video, 3 điểm tham quan); bổ sung assert vào `tests/Integration/Cli/SeedTest.php`

**Checkpoint**: Đại biểu có đủ thông tin đi lại và liên hệ trên điện thoại.

---

## Phase 8: User Story 6 - Cấu hình chung, menu và trang chủ (Priority: P2)

**Goal**: Quản trị viên sửa thông tin chung, menu kéo thả; quản lý slider, đoạn giới thiệu, khối nổi bật.

**Independent Test**: Đổi email liên hệ → footer mọi trang và Contact đổi; kéo "Program" lên đầu → menu công khai đổi thứ tự; tắt một slide → biến mất khỏi trang chủ.

### Tests for User Story 6 ⚠️

- [ ] T140 [P] [US6] HTTP test thông tin chung: chỉ admin; `name`, `year` bắt buộc; `contact_email` sai → 422; `submission_url` không bắt đầu `http://`/`https://` → 422; đổi ngày tổ chức sửa mốc `conference_day`; logo/favicon cập nhật trên header công khai; email mới hiện ở footer, trong `tests/Http/Admin/SettingsTest.php`
- [ ] T141 [P] [US6] HTTP test menu: chỉ admin; lưu cây thứ tự mới; mục cấp 3 → 422 ("tối đa 2 cấp"); mục có cả `page_id` và `external_url` → 422; link ngoài công khai có `target="_blank" rel="noopener"`, trong `tests/Http/Admin/MenuTest.php`
- [ ] T142 [P] [US6] HTTP test trang chủ: editor sửa đoạn giới thiệu (lọc HTML), thêm slide thiếu `alt_text` → 422, tắt slide → không hiện, sắp xếp slide và khối nổi bật, trong `tests/Http/Admin/HomeSettingsTest.php`
- [ ] T143 [P] [US6] Playwright test `tests/e2e/menu-editor.spec.js` cho cây menu (`sortable-list.js` chế độ lồng cấp, `data-max-depth="2"`): kéo "Program" lên đầu và kéo một mục thành mục con → lưu → menu công khai đúng thứ tự và cấp; thử đưa mục xuống cấp 3 → bị chặn kèm thông báo "Menu tối đa 2 cấp"; chỉ dùng bàn phím với nút ▲/▼/→/← làm được cùng thao tác

### Implementation for User Story 6

- [ ] T144 [US6] Viết `src/Controllers/Admin/SettingsController.php` + `templates/admin/settings/form.php` (mọi trường FR-022, ngày tổ chức đọc/ghi mốc `conference_day`, chọn logo/favicon bằng media picker)
- [ ] T145 [US6] Bổ sung phần ghi của `src/Repositories/MenuRepository.php` (`saveTree()` trong transaction, kiểm tra tối đa 2 cấp) và viết `src/Controllers/Admin/MenuController.php` + `templates/admin/menu/index.php` (cây kéo thả dùng `sortable-list.js` hỗ trợ lồng cấp + nút ▲/▼/→/←; chọn trang trong site hoặc nhập link ngoài)
- [ ] T146 [US6] Viết `src/Controllers/Admin/HomeController.php` + `templates/admin/home/index.php`, `slide-form.php`, `highlight-form.php`; bổ sung phần ghi `src/Repositories/HomeRepository.php`
- [ ] T147 [US6] Thêm route `/admin/settings`, `/admin/menu`, `/admin/home/*` vào `src/routes.php`

**Checkpoint**: Ban tổ chức thay đổi khung website không cần lập trình viên.

---

## Phase 9: User Story 7 - Thư viện tệp, thùng rác, nhật ký (Priority: P3)

**Goal**: Quản lý tệp đầy đủ, cảnh báo tệp đang dùng, thùng rác 30 ngày, nhật ký chỉ admin xem, sao lưu hằng ngày.

**Independent Test**: Xoá một diễn giả → khôi phục từ thùng rác đúng vị trí; nhật ký có hai dòng; xoá ảnh đang dùng → cảnh báo liệt kê nơi dùng.

### Tests for User Story 7 ⚠️

- [ ] T148 [P] [US7] Integration test `MediaUsageFinder`: tìm qua mọi cột FK tới `media_files` (logo, favicon, program file, slide, ảnh thành viên, ảnh diễn giả, logo nhà tài trợ, ảnh đại diện trang) và chuỗi `stored_path` trong `pages.body_html`, `keynote_speakers.bio_html`, `conferences.home_intro_html`, trong `tests/Integration/MediaUsageFinderTest.php`
- [ ] T149 [P] [US7] Integration test `TrashService`: liệt kê mục đã xoá ở mọi bảng xoá mềm kèm ngày tự xoá (`deleted_at` + 30 ngày); khôi phục đặt `deleted_at = NULL` giữ thứ tự; khôi phục trang có slug đã bị dùng → yêu cầu slug khác; `purgeExpired()` xoá vĩnh viễn mục quá 30 ngày (và tệp trên đĩa với media), trong `tests/Integration/TrashServiceTest.php`
- [ ] T150 [P] [US7] HTTP test thư viện tệp: tìm theo tên, lọc loại; tải sai loại/quá dung lượng → lỗi nêu rõ giới hạn và loại cho phép; xoá tệp đang dùng → trang xác nhận liệt kê nơi dùng, xác nhận lần hai → thùng rác, trong `tests/Http/Admin/MediaTest.php`
- [ ] T151 [P] [US7] HTTP test thùng rác + nhật ký: editor thấy "Khôi phục" nhưng không có "Xoá vĩnh viễn" và POST purge → 403; admin purge được; `/admin/activity` chỉ admin, lọc theo người/hành động/khoảng ngày, không có route sửa/xoá nhật ký, trong `tests/Http/Admin/TrashActivityTest.php`
- [ ] T152 [P] [US7] Integration test `BackupService` (lệnh `BACKUP DATABASE` được sinh đúng tên tệp có ngày; nén `uploads/` thành `.zip`; giữ 30 bản mới nhất, xoá bản cũ hơn) trong `tests/Integration/BackupServiceTest.php`
- [ ] T153 [P] [US7] Integration test CLI `bin/daily.php` trong `tests/Integration/Cli/DailyTest.php` (với `APP_ENV=testing`, `BackupService` chạy chế độ thử ghi lệnh thay vì thực thi): xoá vĩnh viễn mục thùng rác quá 30 ngày, giữ mục 29 ngày; gọi sao lưu; ghi `activity_logs`; exit code 0; sao lưu lỗi → vẫn dọn thùng rác, in lỗi, exit code ≠ 0

### Implementation for User Story 7

- [ ] T154 [P] [US7] Viết `src/Services/MediaUsageFinder.php`
- [ ] T155 [P] [US7] Viết `src/Services/TrashService.php` (danh sách bảng xoá mềm khai báo một chỗ, kèm nhãn hiển thị tiếng Việt)
- [ ] T156 [P] [US7] Viết `src/Services/BackupService.php` (`BACKUP DATABASE … TO DISK = '{BACKUP_DIR}\conference-YYYYMMDD.bak'` — `BACKUP_DIR` là đường dẫn nhìn từ máy SQL Server; `ZipArchive` cho `uploads/`; dọn bản cũ giữ 30; chế độ thử cho test; thông báo lỗi rõ khi thiếu quyền `db_backupoperator` hoặc không ghi được thư mục)
- [ ] T157 [US7] Hoàn thiện `src/Controllers/Admin/MediaController.php` (index, upload nhiều tệp, sửa mô tả ảnh, delete có xác nhận nơi dùng) + `templates/admin/media/index.php`, `confirm-delete.php`
- [ ] T158 [US7] Viết `src/Controllers/Admin/TrashController.php` + `templates/admin/trash/index.php`, `restore-slug.php`
- [ ] T159 [US7] Viết `src/Controllers/Admin/ActivityController.php` + `templates/admin/activity/index.php` (bảng phân trang `OFFSET … FETCH NEXT 50`, thời gian `dd/mm/yyyy HH:mm`)
- [ ] T160 [US7] Viết `bin/daily.php` (gọi `TrashService::purgeExpired()` rồi `BackupService::run()`, ghi kết quả ra stdout và `activity_logs`) và `docs/van-hanh.md`: tạo lịch Windows Task Scheduler lúc 02:00; cấp vai trò `db_backupoperator`; cấu hình `BACKUP_DIR` khi SQL Server cùng máy (đường dẫn cục bộ) và khác máy (thư mục chia sẻ UNC mà tài khoản dịch vụ SQL Server ghi được và máy web đọc được); cách khôi phục `.bak` và `.zip`
- [ ] T161 [US7] Thêm route media, trash, activity vào `src/routes.php`

**Checkpoint**: Xoá nhầm khôi phục được, truy vết được thay đổi, dữ liệu được sao lưu.

---

## Phase 10: User Story 8 - Sao chép hội thảo sang năm mới (Priority: P3)

**Goal**: Nhân bản hội thảo, đặt hội thảo hiện hành, lưu trữ công khai `/{year}/`, trang Past Conferences.

**Independent Test**: Sao chép 2025 → 2026; sửa trang 2026 không ảnh hưởng 2025; đặt 2026 hiện hành → `/` hiện 2026, `/2025/` hiện bản cũ + dải lưu trữ, `/past-conferences` liệt kê 2025.

### Tests for User Story 8 ⚠️

- [ ] T162 [P] [US8] Integration test `ConferenceCopier` theo bảng sao chép trong data-model.md: chép thông tin chung (năm mới, `is_current = 0`, `has_been_current = 0`), pages + tags (status `draft`, không chép revisions/slug_redirects), menu (ánh xạ lại `page_id`, `parent_id`), slides, highlights, important_dates, committees + members + page_committees, sponsor_tiers + sponsors, contact_persons; KHÔNG chép keynote_speakers, program, `program_file_media_id`; media dùng chung; bỏ qua bản ghi đã xoá mềm; năm trùng → lỗi; lỗi giữa chừng → rollback toàn bộ, trong `tests/Integration/ConferenceCopierTest.php`
- [ ] T163 [P] [US8] HTTP test lưu trữ: `/{year}` và `/{year}/{slug}` của hội thảo đã từng hiện hành → 200 + dải "This is an archived conference" kèm link về `/`; năm hiện hành → 301 về `/`; năm chưa từng hiện hành → 404; `/past-conferences` liệt kê `has_been_current = 1 AND is_current = 0`, mới nhất trước; footer có link "Past Conferences", trong `tests/Http/Public/ArchiveTest.php`
- [ ] T164 [P] [US8] HTTP test quản trị hội thảo: chỉ admin sao chép và đặt hiện hành; editor chuyển được hội thảo đang sửa qua `/admin/switch-conference` (POST, trường `conference_id`); dữ liệu quản trị luôn thuộc hội thảo đang chọn, trong `tests/Http/Admin/ConferencesTest.php`

### Implementation for User Story 8

- [ ] T165 [US8] Viết `src/Services/ConferenceCopier.php` (một transaction, bảng ánh xạ id cũ → id mới cho pages, menu, committees, sponsor_tiers; ghi nhật ký `copy_conference`)
- [ ] T166 [US8] Bổ sung `ConferenceRepository::makeCurrent()` (transaction: bỏ `is_current` cũ, đặt mới, `has_been_current = 1`) và `archived()`
- [ ] T167 [US8] Viết `src/Controllers/Admin/ConferenceController.php` (danh sách, sao chép với ô nhập năm mới, đặt hiện hành có xác nhận, chuyển hội thảo đang sửa) + `templates/admin/conferences/index.php`, `copy.php`; thêm bộ chọn hội thảo vào thanh trên `templates/admin/layout.php`
- [ ] T168 [US8] Viết `src/Controllers/Public/ArchiveController.php` (`/{year}`, `/{year}/{slug}`, `/past-conferences`) dùng lại `HomeController`/`PageController` với hội thảo theo năm; `templates/public/past-conferences.php`, `templates/public/partials/archive-banner.php`; mọi link nội bộ trong trang lưu trữ giữ tiền tố `/{year}/`
- [ ] T169 [US8] Thêm route lưu trữ (đặt trước `/{slug}`) và route `/admin/conferences/*`, `/admin/switch-conference` (POST, trường `conference_id`) vào `src/routes.php`

**Checkpoint**: Dựng website năm mới từ bản sao trong một ngày; các năm cũ vẫn xem được.

---

## Phase 11: Polish & Cross-Cutting Concerns

**Purpose**: Hoàn thiện chất lượng chung cho mọi user story

- [ ] T170 [P] Viết `src/Controllers/Public/SitemapController.php` cho `/sitemap.xml` (chỉ trang công khai của hội thảo hiện hành) và `/robots.txt`, kèm HTTP test trong `tests/Http/Public/SitemapTest.php`
- [ ] T171 [P] Thêm `srcset` (thumbnail 400/1200 px) và `loading="lazy"` cho mọi ảnh trong `templates/public/**`; kiểm tra CSS công khai ≤ ~30 KB
- [ ] T172 [P] Unit test kiểm tra ngôn ngữ trong `tests/Unit/LanguageTest.php`: mọi khoá trong lời gọi `t('…')` ở `templates/public/**` có trong `lang/en.php`, ở `templates/admin/**` có trong `lang/vi.php`; hai file lang không có khoá rỗng; trong template, mọi đoạn chữ có từ 2 chữ cái trở lên nằm giữa hai thẻ HTML, hoặc trong thuộc tính `alt`, `title`, `placeholder`, `aria-label`, phải được tạo bởi `<?= t(...) ?>` hoặc `<?= e(...) ?>` — test liệt kê `tệp:dòng` của từng vi phạm; chỉ bỏ qua `templates/public/partials/preview-banner.php` (ngoại lệ trong contracts/ui-theme.md)
- [ ] T173 [P] HTTP test tiếp cận cơ bản trong `tests/Http/AccessibilityTest.php`: mọi `<img>` trên trang công khai có `alt`; có link "Skip to content"; menu con có `aria-expanded`; mỗi trang có đúng một `<h1>`
- [ ] T174 [P] Viết `docs/huong-dan-giao-dien.md` (bảng "muốn sửa X → mở file Y" theo contracts/ui-theme.md, cách đổi màu trong `theme.css` và kiểm tra tương phản, ví dụ thêm một khối mới)
- [ ] T175 [P] Viết `docs/huong-dan-quan-tri.md` (hướng dẫn ngắn < 15 phút cho ban tổ chức: sửa trang, xuất bản, mốc thời gian, nhập thành viên ban, sao chép năm mới) và `README.md` (cài đặt theo quickstart.md)
- [ ] T176 Rà soát bảo mật đối chiếu research.md R6, R15, R18: CSP công khai và `/admin` đúng chính sách (trình soạn thảo, slider, bản đồ, video vẫn chạy, console không có lỗi CSP — kiểm bằng cách chạy lại toàn bộ test Playwright); cookie `Secure` trên production; `public/uploads/` không thực thi script và gửi `nosniff` (kiểm tra `web.config`); không có tệp SVG nào nhận được qua mọi đường tải lên; ghi kết quả vào `docs/van-hanh.md`
- [ ] T177 Chạy `composer format` và `composer test` (PHPUnit + Playwright) — toàn bộ pass
- [ ] T178 Chạy toàn bộ kịch bản kiểm tra tay và kiểm tra chất lượng (Lighthouse Accessibility ≥ 90, nội dung chính < 3 giây trên 4G giả lập, khôi phục bản sao lưu) trong `specs/001-conference-website-cms/quickstart.md`, ghi kết quả vào `specs/001-conference-website-cms/checklists/quickstart-results.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: không phụ thuộc
- **Foundational (Phase 2)**: sau Setup — CHẶN mọi user story
- **US1 (Phase 3)**: sau Foundational; dùng dữ liệu seed, không cần đăng nhập
- **US5 (Phase 4)**: sau Foundational; độc lập với US1
- **US2 (Phase 5)**: cần US5 (đăng nhập) và các bảng `pages`/`menu_items` của US1 (T054, T055)
- **US3 (Phase 6)**: cần US5; dùng module công khai của US1 (T064) và media upload/picker của US2 (T099, T105)
- **US4 (Phase 7)**: cần US2 (PageService, editor) và US3 (module contacts)
- **US6 (Phase 8)**: cần US5, US1 (menu, slides), media picker US2; `sortable-list.js` và `SortOrderService` đã có từ Phase 2 nên KHÔNG phụ thuộc US3
- **US7 (Phase 9)**: cần US2 (media) — có ích nhất sau US3 vì có nhiều bảng xoá mềm
- **US8 (Phase 10)**: cần US1, US3, US6 (mọi bảng được sao chép đã tồn tại)
- **Polish (Phase 11)**: sau các story muốn phát hành

### User Story Dependencies

```text
Setup → Foundational ─┬─ US1 ─┐
                      └─ US5 ─┴─ US2 ─┬─ US3 ─┬─ US4
                                      │       └─────────┐
                                      ├─ US6 ───────────┼─ US8
                                      └─ US7            │
                                         (sau US3 tốt hơn)
```

### Within Each User Story

- Test viết trước và phải FAIL trước khi cài đặt (Nguyên tắc II); test Playwright của một story chạy được khi phần giao diện của story đó xong
- Migration → Model → Repository → Service → Controller → Template → Route → Seed
- Hoàn thành và pass test của story trước khi sang story tiếp theo

### Parallel Opportunities

- Setup: mọi task [P] của Phase 1 song song sau khi tạo thư mục và `composer.json`
- Foundational: mọi task test [P] song song; các lớp lõi [P] (Request/Response, Router, Database, Session, Validator, Lang/View, Clock, HtmlSanitizer, SortOrderService) song song; các migration [P] song song sau migration `001_create_conferences.sql`
- Trong mỗi story: mọi task test [P] song song; migrations [P] song song; models/repositories [P] song song
- Sau Foundational: US1 và US5 làm song song bởi hai người
- Sau US2: US3, US6, US7 làm song song (khác file)

---

## Parallel Example: User Story 1

```text
# Viết tất cả test US1 cùng lúc:
Task: "T045 Unit test UpcomingDateFinder in tests/Unit/Services/UpcomingDateFinderTest.php"
Task: "T046 Unit test RelatedPagesFinder in tests/Unit/Services/RelatedPagesFinderTest.php"
Task: "T048 HTTP test trang chủ in tests/Http/Public/HomeTest.php"
Task: "T050 HTTP test module important_dates in tests/Http/Public/ImportantDatesTest.php"

# Migrations US1 cùng lúc:
Task: "T053 database/migrations/005_create_important_dates.sql"
Task: "T054 database/migrations/006_create_pages.sql"
Task: "T055 database/migrations/007_create_menu_items.sql"
Task: "T056 database/migrations/008_create_slides_highlights.sql"
```

## Parallel Example: User Story 3

```text
Task: "T116 database/migrations/009_create_committees.sql"
Task: "T117 database/migrations/010_create_keynote_speakers.sql"
Task: "T118 database/migrations/011_create_program.sql"
Task: "T119 database/migrations/012_create_sponsors.sql"
Task: "T120 database/migrations/013_create_contact_persons.sql"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 Setup → Phase 2 Foundational
2. Phase 3 **US1** → dừng, kiểm tra: khách đọc được website (dữ liệu seed) — có thể demo
3. Phase 4 **US5** + Phase 5 **US2** → **MVP thật sự**: ban tổ chức tự sửa và xuất bản trang
4. Kiểm tra theo quickstart.md các dòng US1, US2, US5 rồi mới triển khai

### Incremental Delivery

1. MVP (US1 + US5 + US2) → triển khai
2. US3 (dữ liệu có cấu trúc) + US4 (đi lại, di động) → triển khai trước mùa nộp bài
3. US6 (cấu hình, menu, trang chủ) → triển khai
4. US7 (tệp, thùng rác, nhật ký, sao lưu) → triển khai trước khi nhiều người cùng sửa
5. US8 (sao chép năm mới, lưu trữ) → cần trước kỳ hội thảo năm sau

### Parallel Team Strategy

1. Cả nhóm làm Setup + Foundational
2. Dev A: US1 → US3 → US4; Dev B: US5 → US2 → US6; Dev C (khi có): US7 → US8

---

## Notes

- [P] = khác file, không phụ thuộc task chưa xong
- Nhãn [USx] ánh xạ task tới user story trong spec.md
- Sau mỗi task hoặc nhóm task: `composer test` phải pass
- Tránh: task mơ hồ, hai task sửa cùng file chạy song song, phụ thuộc chéo phá tính độc lập của story
