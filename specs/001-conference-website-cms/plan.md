# Implementation Plan: Website hội thảo khoa học quốc tế kèm trang quản trị nội dung

**Branch**: `001-conference-website-cms` | **Date**: 2026-11-08 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/001-conference-website-cms/spec.md`

## Summary

Website giới thiệu hội thảo khoa học quốc tế (giao diện công khai tiếng Anh) kèm trang quản trị tiếng Việt để ban tổ
chức tự cập nhật toàn bộ nội dung: trang soạn thảo trực quan, mốc thời gian, các ban, diễn giả, chương trình, nhà tài
trợ, liên hệ, menu, thư viện tệp, thùng rác, nhật ký, sao chép hội thảo sang năm mới và lưu trữ công khai các năm cũ.

Cách làm: **PHP thuần + SQL Server**, render phía server bằng template PHP/HTML; một lõi nhỏ tự viết (router, request/
response, session, CSRF, view) thay cho framework; CSS/JS thuần không bước build, toàn bộ biến giao diện gom trong
`theme.css` để người mới dễ sửa. Chỉ 3 thư viện runtime có lý do rõ ràng (HTML Purifier, PHPMailer, OpenSpout) + TinyMCE.
Chi tiết quyết định: [research.md](./research.md).

## Technical Context

**Language/Version**: PHP 8.3+ (strict_types), HTML5, CSS3, JavaScript ES2020 thuần

**Primary Dependencies**: không framework; `ezyang/htmlpurifier`, `phpmailer/phpmailer`, `openspout/openspout`;
TinyMCE 7 tự host; PHP extensions `pdo_sqlsrv`, `gd`, `mbstring`, `fileinfo`, `zip`, `xmlreader`

**Storage**: SQL Server 2019+ (Express dùng được) qua PDO `pdo_sqlsrv`; tệp tải lên trên đĩa (`public/uploads/`);
sao lưu `.bak` + `.zip` hằng ngày

**Testing**: PHPUnit — unit, integration (CSDL `conference_test`, rollback mỗi test), CLI (`bin/*.php`), HTTP (gọi
`App::handle()` trực tiếp); Playwright (Chromium) cho mọi chức năng JavaScript; một lệnh `composer test` chạy cả hai
(Node.js chỉ cần trên máy phát triển/CI)

**Target Platform**: Windows Server + IIS (FastCGI, URL Rewrite qua `web.config`); phát triển bằng `php -S`; code không
phụ thuộc IIS nên chạy được trên Linux/Nginx

**Project Type**: Ứng dụng web render phía server (một project, không tách frontend/backend)

**Performance Goals**: nội dung chính hiển thị < 3 giây trên 4G cho 95% lượt truy cập (SC-002); vài nghìn lượt/ngày lúc cao điểm

**Constraints**: không bước build front-end; trang công khai dùng được khi tắt JS; ảnh ≤ 5 MB, tài liệu ≤ 20 MB; hết phiên
30 phút; khoá đăng nhập 15 phút sau 5 lần sai; tương phản ≥ 4.5:1

**Scale/Scope**: 1 đơn vị tổ chức, 1 hội thảo hiện hành/lúc, ~10–30 trang mỗi kỳ, < 20 tài khoản quản trị, ~25 màn hình quản trị

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.* — đối chiếu constitution **v2.0.0**.

| Nguyên tắc | Cách đáp ứng | Trước Phase 0 | Sau Phase 1 |
|---|---|---|---|
| I. Code dễ đọc | Luồng tường minh `index.php → routes.php → Controller → template`; SQL viết tay trong Repository; không container/facade/ORM; một bộ format (PHP-CS-Fixer chuẩn PSR-12 *chạy qua `composer format` — công cụ dev, xem Complexity Tracking*) | ✅ | ✅ |
| II. Test mọi chức năng | Mỗi controller action có test HTTP luồng chính + một lỗi/biên; mỗi service có unit/integration test; mỗi script `bin/*.php` có test CLI; mỗi file JS có test Playwright; sửa lỗi kèm test tái hiện; một lệnh `composer test` | ✅ | ✅ (sau /speckit-analyze: bổ sung test JS, dashboard, media picker, CLI) |
| III. Ngôn ngữ theo đối tượng | Công khai: `lang/en.php`, `lang="en"`, ngày `15 March 2026`; quản trị: `lang/vi.php`, `lang="vi"`, ngày `dd/mm/yyyy`; chuỗi gom một nơi; múi giờ Asia/Ho_Chi_Minh | ✅ | ✅ |
| IV. Đơn giản | Không framework, không SPA, không hàng đợi; hẹn giờ xuất bản bằng điều kiện truy vấn; một script `bin/daily.php`; xoá mềm bằng cột; tìm nơi dùng tệp lúc xoá thay vì bảng đồng bộ | ✅ | ✅ |
| V. Tối thiểu thư viện | 3 thư viện runtime + TinyMCE; công cụ dev PHPUnit, PHP-CS-Fixer, Playwright — mỗi cái có lý do trong research.md (bảng "Tổng hợp phụ thuộc"); khoá phiên bản bằng `composer.lock`, `package-lock.json` | ✅ | ✅ |
| Ràng buộc kỹ thuật | Kiểm tra hợp lệ phía server cho mọi biểu mẫu, lỗi đúng ngôn ngữ giao diện (kể cả trang lỗi); bí mật trong `.env`; responsive từ 360 px; không nhận SVG; CSP theo R18 | ✅ | ✅ |

**Kết quả**: Đạt, không có vi phạm cần biện minh. Các lựa chọn cần ghi lý do theo Nguyên tắc IV/V nằm ở Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/001-conference-website-cms/
├── plan.md              # File này
├── research.md          # Phase 0: quyết định công nghệ
├── data-model.md        # Phase 1: bảng, cột, ràng buộc, trạng thái
├── quickstart.md        # Phase 1: cài đặt + kịch bản kiểm tra
├── contracts/
│   ├── public-routes.md # Đường dẫn và khối hiển thị công khai
│   ├── admin-routes.md  # Đường dẫn quản trị + quyền
│   ├── import-format.md # Định dạng tệp nhập thành viên ban
│   └── ui-theme.md      # Quy ước giao diện cho người mới sửa
└── tasks.md             # Phase 2 (/speckit-tasks)
```

### Source Code (repository root)

```text
public/                       # Thư mục gốc của web server — chỉ phần này truy cập được từ ngoài
├── index.php                 # Front controller: nạp bootstrap, gọi App::handle()
├── web.config                # IIS: rewrite về index.php, chặn thực thi script trong uploads/
├── assets/
│   ├── css/theme.css         # Biến màu, font, khoảng cách — chỗ người mới sửa đầu tiên
│   ├── css/public.css
│   ├── css/admin.css
│   ├── js/                   # menu-mobile.js, sortable-list.js, confirm-delete.js, media-picker.js, editor.js
│   └── vendor/tinymce/
└── uploads/                  # Tệp người dùng tải lên (theo năm/tháng)

src/
├── bootstrap.php             # Đọc .env, tạo kết nối DB, đăng ký autoload
├── routes.php                # Bảng route: [phương thức, mẫu đường dẫn, Controller::action, quyền]
├── Core/                     # Lõi nhỏ tự viết
│   ├── App.php               # handle(Request): Response — khớp route, kiểm quyền, CSRF, gọi controller
│   ├── Router.php  Request.php  Response.php
│   ├── Database.php          # Bọc PDO: query(), first(), execute(), transaction()
│   ├── Session.php  Csrf.php  Auth.php  View.php  Lang.php  Validator.php  Flash.php
│   ├── Clock.php             # now() — đặt được giá trị trong test
│   └── helpers.php           # e(), t(), format_date(), partial(), url()
├── Repositories/             # SQL cho từng bảng: PageRepository, ImportantDateRepository, ...
├── Services/                 # Logic nghiệp vụ không tầm thường
│   ├── UpcomingDateFinder.php    ConferenceCopier.php      CommitteeImporter.php
│   ├── HtmlSanitizer.php         MediaUploader.php         MediaUsageFinder.php
│   ├── PasswordResetService.php  LoginThrottle.php         RelatedPagesFinder.php
│   ├── ActivityLogger.php        TrashService.php          BackupService.php
│   ├── PageService.php           SlugValidator.php         SortOrderService.php
│   ├── EmbedConverter.php        PublicPageContext.php     Mailer.php
│   └── DashboardService.php
├── Controllers/
│   ├── Public/               # HomeController, PageController, ArchiveController, SitemapController
│   └── Admin/                # AuthController, DashboardController, PageController, DateController, ...
└── Models/                   # Lớp dữ liệu đơn giản (readonly properties), không có logic DB

templates/
├── public/                   # layout.php, home.php, page.php, past-conferences.php, 404.php
│   ├── partials/             # header.php, footer.php, menu.php, share-buttons.php, related-pages.php
│   └── modules/              # important_dates.php, committees.php, keynote_speakers.php, program.php, sponsors.php, contacts.php
└── admin/                    # layout.php + một thư mục cho mỗi nhóm chức năng

lang/
├── en.php                    # Chuỗi giao diện công khai
└── vi.php                    # Chuỗi giao diện quản trị

database/migrations/          # 001_create_conferences.sql, 002_..., chạy theo thứ tự bởi bin/migrate.php
bin/                          # migrate.php, seed.php, create-admin.php, daily.php
docs/huong-dan-giao-dien.md   # Hướng dẫn người mới sửa giao diện (theo contracts/ui-theme.md)
tests/
├── Unit/
├── Integration/              # gồm Integration/Cli/ cho bin/*.php
├── Http/
├── e2e/                      # Playwright: *.spec.js cho từng file JS
├── fixtures/                 # tệp mẫu nhập Excel/CSV, ảnh, PDF dùng trong test
└── Support/                  # DatabaseTestCase, HttpTestCase
public/assets/templates/      # tệp mẫu nhập thành viên ban (.xlsx, .csv)
composer.json  phpunit.xml  package.json  playwright.config.js  .env.example
```

**Structure Decision**: Một project web render phía server. `public/` là thư mục gốc web duy nhất (mã nguồn, `.env`,
template nằm ngoài nên không tải được từ trình duyệt). Phân tách theo vai trò dễ đoán: **Controller** nhận request và
chọn template, **Repository** chứa SQL, **Service** chứa logic nghiệp vụ có test riêng, **templates/** chỉ hiển thị.
Người sửa giao diện chỉ cần `templates/`, `public/assets/css/`, `lang/`.

## Thứ tự triển khai đề xuất (đầu vào cho /speckit-tasks)

1. **Nền tảng**: lõi `Core/`, migrations, seed, test harness, layout công khai + quản trị, `theme.css`.
2. **US5** Đăng nhập, phân quyền, tài khoản (tiên quyết cho mọi màn hình quản trị).
3. **US1 + US2** Thông tin chung, mốc thời gian, trang nội dung (soạn thảo, trạng thái, hẹn giờ, xem trước, phiên bản),
   trang chủ, các trang công khai cốt lõi → **MVP ra mắt được**.
4. **US3 + US4** Ban (kèm nhập Excel/CSV), diễn giả, chương trình, nhà tài trợ, liên hệ, Travel Info.
5. **US6** Menu kéo thả, slider, khối nổi bật.
6. **US7** Thư viện tệp đầy đủ, thùng rác, nhật ký, `bin/daily.php` (dọn thùng rác + sao lưu).
7. **US8** Sao chép hội thảo, lưu trữ `/{year}/`, Past Conferences.

## Complexity Tracking

Không có vi phạm constitution. Bảng dưới ghi lý do cho các lựa chọn mà Nguyên tắc IV/V yêu cầu phải biện minh.

| Lựa chọn | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| HTML Purifier | Nội dung soạn thảo hiển thị cho khách → phải chống XSS | `strip_tags` không lọc thuộc tính nguy hiểm; tự viết bộ lọc kém an toàn |
| PHPMailer | Gửi email đặt lại mật khẩu qua SMTP có xác thực/TLS | `mail()` không hỗ trợ SMTP xác thực trên Windows/IIS |
| OpenSpout | Đọc `.xlsx` cho nhập thành viên ban (spec yêu cầu Excel) | Chỉ CSV không đáp ứng spec; đọc zip/XML tay dễ sai |
| TinyMCE 7 | Trình soạn thảo trực quan có bảng, ảnh, video, tệp | Tự viết `contenteditable` không khả thi; Quill/Trix yếu về bảng |
| PHP-CS-Fixer (dev) | Constitution I yêu cầu một bộ quy tắc format duy nhất, tự động | Format tay không nhất quán |
| Playwright (dev, cần Node.js trên máy phát triển) | Constitution II: chức năng JS (menu, kéo thả, chọn tệp, soạn thảo) phải có test tự động | Kiểm tra tay vi phạm Nguyên tắc II; jsdom không mô phỏng đáng tin kéo thả/clipboard |
| Lõi tự viết (~600 dòng) thay framework | Giữ luồng tường minh cho người mới, ít phụ thuộc | Laravel/Slim thêm nhiều package và khái niệm ẩn |
