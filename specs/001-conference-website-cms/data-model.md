# Data Model: Website hội thảo khoa học quốc tế

**Feature**: `001-conference-website-cms` | **Date**: 2026-11-08 | **CSDL**: SQL Server 2019+

## Quy ước chung

- Khoá chính `id INT IDENTITY`. Chữ dùng `NVARCHAR` (tiếng Việt có dấu). Thời điểm dùng `DATETIME2`, lưu **UTC**,
  hiển thị theo Asia/Ho_Chi_Minh. Ngày thuần (không giờ) dùng `DATE`.
- Mọi bảng có `created_at`, `updated_at` (không liệt kê lại bên dưới).
- **Xoá mềm** (thùng rác, FR-039): bảng có đánh dấu 🗑 có thêm `deleted_at DATETIME2 NULL`, `deleted_by INT NULL`.
  Mọi truy vấn thông thường lọc `deleted_at IS NULL`. Quá 30 ngày → `bin/daily.php` xoá vĩnh viễn.
- **Hiện/Ẩn** (FR-035a): bảng có đánh dấu 👁 có `is_visible BIT NOT NULL DEFAULT 1`; trang công khai chỉ lấy `is_visible = 1`.
- **Thứ tự**: `sort_order INT NOT NULL`, nhỏ hiện trước.
- **Thuộc hội thảo**: bảng có `conference_id` là dữ liệu riêng của một kỳ hội thảo (sao chép được, FR-038).

## Sơ đồ quan hệ

```text
conferences 1─* pages 1─* page_revisions
     │            ├─* page_tags
     │            ├─* page_committees *─1 committees 1─* committee_members
     │            └─* slug_redirects
     ├─* menu_items (parent_id → menu_items)
     ├─* slides, highlights
     ├─* important_dates
     ├─* committees
     ├─* keynote_speakers
     ├─* program_days 1─* program_sessions 1─* program_items
     ├─* sponsor_tiers 1─* sponsors
     └─* contact_persons
media_files ← được tham chiếu bởi logo, favicon, ảnh, tệp chương trình… (dùng chung mọi hội thảo)
users 1─* password_reset_tokens ; users 1─* activity_logs
```

---

## conferences — Hội thảo (một kỳ theo năm)

| Cột | Kiểu | Ràng buộc / ghi chú |
|---|---|---|
| name | NVARCHAR(255) | bắt buộc — tên hội thảo |
| theme | NVARCHAR(255) | chủ đề, ví dụ "Practices, Opportunities and Challenges" |
| year | INT | bắt buộc; **UNIQUE** — dùng làm tiền tố đường dẫn lưu trữ `/{year}/...` |
| venue | NVARCHAR(500) | địa điểm |
| conference_language | NVARCHAR(100) | ngôn ngữ hội thảo hiển thị cho khách, ví dụ "English" |
| public_language | VARCHAR(5) | mã giao diện công khai, mặc định `en` (Nguyên tắc III) |
| logo_media_id, favicon_media_id | INT NULL | FK `media_files` |
| contact_email | NVARCHAR(255) | định dạng email hợp lệ |
| contact_phone | NVARCHAR(50) | |
| footer_text | NVARCHAR(1000) | |
| copyright_text | NVARCHAR(255) | |
| submission_url | NVARCHAR(1000) NULL | phải bắt đầu `https://` hoặc `http://`; NULL → nút "Submission opening soon" |
| facebook_url, linkedin_url, x_url, youtube_url | NVARCHAR(1000) NULL | |
| home_intro_html | NVARCHAR(MAX) | đoạn giới thiệu trang chủ (đã lọc HTML) |
| program_file_media_id | INT NULL | ảnh/PDF chương trình thay thế (FR-033) |
| is_current | BIT | **đúng một** hội thảo có `is_current = 1` (unique filtered index) |
| has_been_current | BIT | đặt `1` khi lần đầu được làm hiện hành; chỉ những kỳ này được xem công khai ở `/{year}/` (FR-038a) |

Ngày tổ chức **không** lưu ở đây: lấy từ `important_dates` có `special_role = 'conference_day'` (một nguồn duy nhất, FR-002).
Biểu mẫu "Thông tin chung" hiển thị và sửa trực tiếp mốc đó.

## users — Tài khoản quản trị

| Cột | Kiểu | Ràng buộc / ghi chú |
|---|---|---|
| email | NVARCHAR(255) | **UNIQUE**, lưu chữ thường |
| full_name | NVARCHAR(255) | bắt buộc |
| password_hash | NVARCHAR(255) | `password_hash()`; mật khẩu ≥ 8 ký tự (FR-018) |
| role | VARCHAR(10) | `admin` \| `editor` |
| is_active | BIT | `0` = bị khoá bởi quản trị viên |
| failed_login_count | INT | đặt lại 0 khi đăng nhập đúng |
| locked_until | DATETIME2 NULL | = now + 15 phút khi sai lần thứ 5 (FR-017) |
| session_epoch | INT | tăng khi đổi mật khẩu / bị khoá → phiên cũ hết hiệu lực |
| last_login_at | DATETIME2 NULL | |

**Quy tắc**: luôn còn ≥ 1 tài khoản `role = admin AND is_active = 1` — không cho khoá/hạ vai trò quản trị viên cuối cùng.

## password_reset_tokens

| Cột | Kiểu | Ghi chú |
|---|---|---|
| user_id | INT | FK `users` |
| token_hash | CHAR(64) | SHA-256 của token gửi qua email; **UNIQUE** |
| expires_at | DATETIME2 | = tạo + 60 phút |
| used_at | DATETIME2 NULL | khác NULL → không dùng lại được |

## pages 🗑 — Trang nội dung

| Cột | Kiểu | Ràng buộc / ghi chú |
|---|---|---|
| conference_id | INT | FK |
| title | NVARCHAR(255) | bắt buộc |
| slug | NVARCHAR(150) | chữ thường a–z, 0–9, `-`; **UNIQUE trong (conference_id) với bản ghi chưa xoá**; không trùng đường dẫn hệ thống (`admin`, `assets`, `uploads`, `past-conferences`, số năm) |
| body_html | NVARCHAR(MAX) | đã lọc bằng HTML Purifier |
| module | VARCHAR(30) | khối dữ liệu hiển thị dưới nội dung: `none`, `important_dates`, `committees`, `keynote_speakers`, `program`, `sponsors`, `contacts` |
| seo_title | NVARCHAR(255) NULL | rỗng → dùng `title` |
| seo_description | NVARCHAR(500) NULL | rỗng → 160 ký tự đầu của nội dung |
| featured_media_id | INT NULL | ảnh chia sẻ; rỗng → logo hội thảo |
| status | VARCHAR(10) | `draft` \| `published` \| `hidden` |
| published_at | DATETIME2 NULL | bắt buộc khi `published`; nếu ở tương lai = hẹn giờ |
| version | INT | khoá lạc quan (R13); +1 mỗi lần lưu |
| created_by, updated_by | INT | FK `users` |

**Hiển thị công khai khi**: `status = 'published' AND published_at <= now AND deleted_at IS NULL`.

```text
Trạng thái trang:
  draft ──Xuất bản──▶ published (published_at ≤ now: hiện ngay; > now: hẹn giờ, chưa hiện)
  published ──Ẩn──▶ hidden ──Xuất bản──▶ published
  bất kỳ ──Xoá──▶ (thùng rác) ──Khôi phục──▶ trạng thái cũ ; ──30 ngày / Xoá vĩnh viễn (admin)──▶ mất
```

## page_revisions — Lịch sử phiên bản (FR-026)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| page_id | INT | FK |
| revision_no | INT | tăng dần trong một trang; UNIQUE (page_id, revision_no) |
| title, body_html, seo_title, seo_description, featured_media_id, module | như `pages` | ảnh chụp nội dung |
| created_by | INT | người lưu |

Mỗi lần lưu trang tạo một dòng. **Khôi phục** = sao chép dòng cũ thành nội dung hiện tại và tạo một revision mới.

## page_tags

| Cột | Kiểu | Ghi chú |
|---|---|---|
| page_id | INT | FK |
| tag | NVARCHAR(50) | chữ thường, bỏ khoảng trắng thừa; PK (page_id, tag) |

Dùng để lọc trong quản trị và chọn "Related pages" (FR-013, FR-024). Không hiển thị công khai.

## slug_redirects

| Cột | Kiểu | Ghi chú |
|---|---|---|
| conference_id | INT | |
| old_slug | NVARCHAR(150) | UNIQUE (conference_id, old_slug) |
| page_id | INT | FK — truy cập slug cũ → 301 tới slug hiện tại |

## menu_items 🗑 — Menu (FR-028)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| conference_id | INT | |
| parent_id | INT NULL | FK `menu_items`; **tối đa 2 cấp** (mục con không có con) |
| label | NVARCHAR(100) | bắt buộc |
| page_id | INT NULL | trỏ tới trang trong site |
| external_url | NVARCHAR(1000) NULL | link ngoài, mở tab mới |
| sort_order | INT | |

**Ràng buộc**: đúng một trong `page_id`, `external_url` có giá trị, hoặc cả hai NULL (mục cha chỉ để mở menu con).
Mục trỏ tới trang không công khai → không hiện ở menu công khai.

## slides 🗑👁, highlights 🗑👁 — Trang chủ (FR-029)

- **slides**: `conference_id`, `media_id` (bắt buộc), `title`, `alt_text` (bắt buộc, FR-042), `link_url NULL`, `sort_order`.
- **highlights**: `conference_id`, `title`, `body_text NVARCHAR(500)`, `link_url NULL`, `sort_order`.

## important_dates 🗑👁 — Mốc thời gian (FR-004, FR-030)

| Cột | Kiểu | Ràng buộc / ghi chú |
|---|---|---|
| conference_id | INT | |
| label | NVARCHAR(255) | bắt buộc, ví dụ "Abstract submission deadline" |
| status | VARCHAR(10) | `normal` \| `extended` \| `tba` |
| date_start | DATE NULL | bắt buộc trừ khi `tba` |
| date_end | DATE NULL | ≥ `date_start`; dùng cho mốc kéo dài nhiều ngày (ngày hội thảo) |
| original_date | DATE NULL | bắt buộc khi `extended`; hiện gạch ngang |
| special_role | VARCHAR(30) NULL | `abstract_deadline` \| `full_paper_deadline` \| `conference_day`; UNIQUE (conference_id, special_role) khi khác NULL |
| sort_order | INT | |

**Mốc "sắp tới"**: mốc hiện, không `tba`, có `date_start >= hôm nay (giờ VN)`, sớm nhất; nếu nhiều mốc cùng ngày
thì lấy `sort_order` nhỏ nhất. Không có mốc nào thoả → không làm nổi bật.

## committees 🗑👁, page_committees, committee_members 🗑👁 (FR-005, FR-031)

- **committees**: `conference_id`, `name NVARCHAR(255)` (ví dụ "Steering Committee"), `sort_order`.
- **page_committees**: `page_id`, `committee_id`, `sort_order` — một trang chứa nhiều ban; PK (page_id, committee_id).
- **committee_members**:

| Cột | Kiểu | Ghi chú |
|---|---|---|
| committee_id | INT | FK |
| academic_title | NVARCHAR(100) | học hàm/học vị, ví dụ "Assoc. Prof. Dr." |
| full_name | NVARCHAR(255) | **bắt buộc** |
| position_affiliation | NVARCHAR(500) | chức vụ và đơn vị |
| committee_role | NVARCHAR(100) | vai trò trong ban, ví dụ "Chair" |
| photo_media_id | INT NULL | |
| sort_order | INT | STT hiển thị = vị trí trong danh sách đã lọc |

## keynote_speakers 🗑👁 (FR-006, FR-032)

`conference_id`, `photo_media_id NULL`, `academic_title`, `full_name` (bắt buộc), `affiliation`, `country`,
`talk_title`, `bio_html NVARCHAR(MAX)` (đã lọc), `sort_order`.

## program_days 🗑, program_sessions 🗑, program_items 🗑👁 (FR-007, FR-033)

- **program_days**: `conference_id`, `day_date DATE` (bắt buộc), `title NVARCHAR(255) NULL`, `sort_order`.
- **program_sessions**: `program_day_id`, `title` (bắt buộc), `room NVARCHAR(100) NULL`, `sort_order`.
- **program_items**: `program_session_id`, `start_time TIME`, `end_time TIME` (> start_time), `content` (bắt buộc),
  `presenter NULL`, `room NULL` (rỗng → dùng phòng của phiên), `sort_order`.

**Hiển thị**: nếu hội thảo có ít nhất một `program_items` hiện → bảng chương trình; ngược lại nếu có
`conferences.program_file_media_id` → ảnh/PDF; ngược lại → "Information will be updated soon."

## sponsor_tiers 🗑, sponsors 🗑👁 (FR-009, FR-034)

- **sponsor_tiers**: `conference_id`, `name NVARCHAR(100)` (ví dụ "Organizer", "Co-organizer", "Sponsor"), `sort_order`.
- **sponsors**: `conference_id`, `sponsor_tier_id`, `name` (bắt buộc), `logo_media_id` (bắt buộc), `website_url NULL`, `sort_order`.

## contact_persons 🗑👁 (FR-010, FR-035)

`conference_id`, `full_name` (bắt buộc), `affiliation`, `email` (định dạng hợp lệ), `phone` (chỉ số, khoảng trắng, `+`, `.`, `-`), `sort_order`.

## media_files 🗑 — Thư viện tệp (FR-036)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| original_name | NVARCHAR(255) | tên gốc, dùng để tìm kiếm |
| stored_path | NVARCHAR(500) | `uploads/2026/11/<random>.<ext>` — tên lưu ngẫu nhiên |
| mime_type | VARCHAR(100) | kiểm tra bằng `finfo`, không tin phần mở rộng |
| size_bytes | INT | ảnh ≤ 5 MB; tài liệu ≤ 20 MB |
| kind | VARCHAR(10) | `image` \| `document` |
| width, height | INT NULL | với ảnh |
| thumb_small_path, thumb_large_path | NVARCHAR(500) NULL | 400 px và 1200 px (không tạo cho GIF động) |
| alt_text | NVARCHAR(500) NULL | mô tả ảnh mặc định |
| uploaded_by | INT | |

Loại cho phép: JPG, PNG, WebP, GIF (ảnh) và PDF, DOC, DOCX (tài liệu). **Không nhận SVG** (xem research.md R6).
Dùng chung giữa các hội thảo — không nhân bản khi sao chép (FR-038).

## activity_logs — Nhật ký (FR-037)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| user_id | INT NULL | NULL với đăng nhập thất bại không xác định được tài khoản |
| action | VARCHAR(30) | `create`, `update`, `delete`, `restore`, `purge`, `publish`, `unpublish`, `login`, `login_failed`, `logout`, `account_change`, `copy_conference`, `import` |
| entity_type | VARCHAR(30) | tên bảng, ví dụ `pages` |
| entity_id | INT NULL | |
| entity_label | NVARCHAR(255) | tên dễ đọc tại thời điểm ghi (vẫn đọc được khi đối tượng đã bị xoá) |
| conference_id | INT NULL | |
| ip_address | VARCHAR(45) | |
| created_at | DATETIME2 | |

Chỉ thêm, không sửa, không xoá (không có route sửa/xoá). Màn hình Nhật ký đọc toàn bộ (chỉ admin). Bảng điều
khiển (FR-021) đọc 10 dòng mới nhất với `action IN ('create','update','delete','restore','publish','unpublish')`
và `conference_id` = hội thảo đang sửa, chỉ lấy cột người thực hiện, hành động, `entity_label`, `created_at`
(không lấy `ip_address`).

---

## Sao chép hội thảo (FR-038)

Sao chép trong **một transaction**:

| Được sao chép | Không sao chép |
|---|---|
| thông tin chung (năm mới do người dùng nhập, `is_current = 0`, `has_been_current = 0`) | keynote_speakers |
| pages + page_tags (trạng thái → `draft`; không chép revisions, slug_redirects) | program_days/sessions/items, program_file_media_id |
| menu_items (ánh xạ lại `page_id`, `parent_id` sang bản mới) | media_files (dùng chung, chỉ chép tham chiếu) |
| slides, highlights, important_dates | activity_logs |
| committees + committee_members + page_committees | |
| sponsor_tiers + sponsors, contact_persons | |

Bản ghi đã xoá mềm không được sao chép.
