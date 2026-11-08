# Kết quả chạy quickstart.md (T178)

Ngày chạy: 08/11/2026. Máy: Windows 11, PHP 8.3 NTS, SQL Server 2022 Express (`localhost\SQLEXPRESS`), ODBC Driver 17.
Site chạy thử: `php -S localhost:8000`, CSDL `conference` (dữ liệu mẫu của `bin/seed.php`).
Các kịch bản được thao tác bằng trình duyệt Chromium do Claude điều khiển qua Playwright, không phải người dùng thật.
Sau khi chạy xong, CSDL chạy thử được khôi phục từ bản sao lưu tạo trước đó (xem mục 3).

## 1. Test tự động (`composer test`)

| Phần | Kết quả |
|---|---|
| PHPUnit (unit, integration, CLI, HTTP) | 342 test, 1063 assertion, **pass** |
| Playwright (Chromium, desktop 1280 + điện thoại 360) | 25 pass, 17 bỏ qua có chủ ý (test chỉ chạy ở một khung màn hình), **không có vi phạm CSP** |
| `composer format` | 0 tệp cần sửa |

## 2. Kịch bản theo user story

| # | Kịch bản | Cách kiểm | Kết quả |
|---|---|---|---|
| US1 | `/` hiện tên, chủ đề, ngày, địa điểm, ngôn ngữ, hai hạn nộp, lối tắt CFP / Paper Submission | Trình duyệt + `HomeTest` | Đạt |
| US1 | Xoá link nộp bài → nút "Submission opening soon" bị vô hiệu | `SubmissionButtonTest` | Đạt |
| US1 | Gia hạn hạn nộp tóm tắt 31/01 → 15/02/2027 | Trình duyệt (sửa trong admin, xem bằng phiên khách) | Đạt: `/important-dates` và `/` cùng hiện ~~31 January 2027~~ 15 February 2027 + "Extended" |
| US2 | Sửa và xuất bản trang trong < 5 phút | `editor.spec.js`, `PagesTest` | Đạt về chức năng. **Chưa bấm giờ với người dùng thật**: cần một thành viên ban tổ chức làm thử |
| US2 | Bản nháp → khách thấy 404; Xem trước vẫn xem được | `PageTest`, `PagesTest::test_preview_shows_draft_with_banner` | Đạt |
| US2 | Hai tab cùng sửa → cảnh báo xung đột | `PagesTest::test_edit_conflict_shows_warning_and_keeps_other_version` | Đạt |
| US3 | Nhập 30 dòng; nhập tệp có dòng thiếu họ tên | `CommitteesTest` (2 test import) | Đạt: thêm đủ 30; tệp lỗi không thêm dòng nào |
| US3 | Ẩn nhà tài trợ | `SponsorsTest::test_hidden_sponsor_disappears` | Đạt |
| US4 | `/contact` ở 360 px | Trình duyệt (khung điện thoại, chạm) | Đạt: menu thu gọn, bấm Menu thì mở; cuộn ngang 0 px; 3 link `tel:`, 4 link `mailto:` |
| US5 | Biên tập viên mở `/admin/users`, `/admin/activity` | `UsersTest`, `TrashActivityTest` | Đạt: 403 |
| US5 | Sai mật khẩu 5 lần | `LoginTest::test_locked_after_five_failures_even_with_correct_password` | Đạt |
| US6 | Kéo thả / nút ▲ đổi thứ tự menu | `menu-editor.spec.js`, `sortable.spec.js` | Đạt |
| US7 | Xoá tệp đang dùng | Trình duyệt + `MediaTest` | Đạt: liệt kê 4 nơi dùng (logo nhà tài trợ); bấm Huỷ thì tệp vẫn còn. Đường vào thùng rác/khôi phục: `TrashActivityTest`, `confirm-delete.spec.js` |
| US8 | Sao chép 2027 → 2028, đặt 2028 hiện hành | Trình duyệt + `ConferencesTest`, `ArchiveTest` | Đạt: `/` hiện 2028; trang diễn giả của 2028 chưa công bố (404); `/2027` hiện bản cũ, dải "This is an archived conference", link menu giữ tiền tố `/2027/`; `/past-conferences` liệt kê 2027 |

## 3. Kiểm tra chất lượng

### Tiếp cận

- Lighthouse 12.6.1 (chạy tạm qua `npx`, không thêm vào dự án), chế độ điện thoại: **Accessibility 100** ở `/`,
  `/important-dates`, `/call-for-papers`, `/keynote-speakers`, `/contact` (yêu cầu ≥ 90).
- Bàn phím: Tab / Enter / Space / Esc trên menu: `public-navigation.spec.js`. Cấu trúc (alt, một h1, skip link,
  aria-expanded) trên mọi loại trang công khai: `tests/Http/AccessibilityTest.php`.

### Tốc độ (điện thoại, 4G giả lập)

| Trang | LCP (simulate) | LCP (devtools throttling) | CLS |
|---|---|---|---|
| `/` | 1,2 s | 1,9 s | 0 |
| `/important-dates` | 1,2 s | | 0 |
| `/call-for-papers`, `/keynote-speakers`, `/contact` | 1,1–1,2 s | | 0 |

Nội dung chính hiển thị **< 3 giây**: đạt.

Phát hiện và đã sửa trong lần chạy này:
- **CLS 0,237 ở trang chủ**: menu điện thoại hiện đầy đủ (dự phòng khi tắt JS) rồi mới thu lại khi `menu-mobile.js`
  (defer) chạy, làm nội dung nhảy. Đã chuyển việc đặt class `js` sang `public/assets/js/js-enabled.js`, nạp đồng bộ
  trong `<head>` (CSP cấm script inline). Đo lại: CLS = 0. Có test Playwright mới đo layout shift < 0,1.
- Điểm Performance khi throttling "simulate" là 59–71, do TBT khoảng 3 giây mà Lighthouse ghi là "Unattributable /
  Other" (JS của trang chỉ chạy khoảng 56 ms). Đo bằng throttling devtools (giới hạn mạng/CPU thật): Performance 98,
  TBT 0 ms. Kết luận: con số TBT đó đến từ mô hình giả lập của Lighthouse, không phải từ code của trang.

### Sao lưu và khôi phục

**Lỗi nghiêm trọng phát hiện và đã sửa**: `bin/daily.php` báo "Đã sao lưu …conference-YYYYMMDD.bak" nhưng không có
tệp `.bak`, và `msdb.dbo.backupset` không có bản ghi nào. Nguyên nhân: với driver `pdo_sqlsrv`, `PDO::exec()` trả về
sớm, rồi câu lệnh bị huỷ khi còn thông báo tiến độ chưa đọc ("Processed N pages…"), mà không báo lỗi. Test cũ chỉ chạy
ở chế độ dry-run nên không bắt được.

Đã sửa:
- `Database::exec()` đọc hết mọi kết quả của batch (`nextRowset()`).
- `BackupService` kiểm tra tệp `.bak` thật sự tồn tại và không rỗng.

Test mới:
- `DatabaseTest::test_exec_runs_whole_batch_even_after_many_info_messages`
- `DailyTest::test_real_backup_writes_bak_file_and_records_it_in_sql_server`: sao lưu thật vào thư mục con của
  `BACKUP_DIR`; bị bỏ qua kèm lý do trên máy chưa cấu hình `BACKUP_DIR`.

Sau khi sửa:
- `php bin/daily.php`: mã thoát 0; có `conference-20261108.bak` (5,2 MB) và `uploads-20261108.zip`.
- `RESTORE … WITH MOVE` sang CSDL tạm `conference_restore_check`: thành công. Số dòng conferences / pages /
  menu_items / users / schema_migrations khớp bản gốc (1 / 10 / 11 / 1 / 13). Đã xoá CSDL tạm.
- Giải nén `.zip`: có thư mục `uploads/` với đủ tệp mẫu, đúng như `docs/van-hanh.md` mô tả.
- `RESTORE DATABASE conference … WITH REPLACE` đè lên CSDL chạy thử, sau kịch bản US1/US8: thành công. Website hiện
  lại hội thảo 2027 với mốc chưa gia hạn.

Cấu hình máy chạy thử: đã tạo `C:\backup\conference` và cấp quyền Modify cho `NT Service\MSSQL$SQLEXPRESS`.

### Ngôn ngữ

`tests/Unit/LanguageTest.php` (tự động): mọi khoá `t()` có trong file lang tương ứng; không có chữ viết thẳng trong
template (trừ ngoại lệ `preview-banner.php`); không có khoá rỗng. Đạt.

## 4. Còn cần người thật làm

- Bấm giờ US2 (< 5 phút) với một thành viên ban tổ chức chưa từng dùng hệ thống, theo `docs/huong-dan-quan-tri.md`.
- Trên máy chủ thật: cấp quyền cho thư mục sao lưu (hoặc UNC nếu SQL Server ở máy khác), đặt lịch Task Scheduler,
  bật HTTPS và đặt `APP_URL=https://…`.
