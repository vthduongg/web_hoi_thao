# Contract: Đường dẫn quản trị

Tiền tố `/admin`. Giao diện tiếng Việt (`lang="vi"`, chuỗi từ `lang/vi.php`). Mọi `POST` bắt buộc có token CSRF
hợp lệ, sai → **419** với trang "Phiên làm việc đã hết hạn, vui lòng tải lại trang."

**Quy tắc chung**
- Chưa đăng nhập → **302** tới `/admin/login?next=…`.
- Đăng nhập nhưng thiếu quyền → **403** "Bạn không có quyền thực hiện thao tác này." (kiểm tra ở server, FR-020).
- Lưu thành công → **302** về trang danh sách/sửa + thông báo flash xanh; lỗi kiểm tra → **422**, hiện lại biểu mẫu
  với lỗi tiếng Việt cạnh từng trường.
- Dữ liệu thuộc **hội thảo đang chọn để sửa** (lưu trong session, mặc định là hội thảo hiện hành; đổi ở thanh trên cùng).
- Mọi thao tác ghi đều được ghi `activity_logs`.
- Trang lỗi 403/404/419/500 dưới `/admin` dùng `templates/admin/error.php` (tiếng Việt).
- Header CSP khu vực quản trị theo research.md R18.

Quyền: **A** = chỉ quản trị viên; **E** = quản trị viên và biên tập viên.

## Tài khoản & đăng nhập

| Phương thức + đường dẫn | Quyền | Mô tả |
|---|---|---|
| GET/POST `/admin/login` | khách | Đăng nhập email + mật khẩu; sai 5 lần → khoá 15 phút |
| POST `/admin/logout` | E | Đăng xuất |
| GET/POST `/admin/forgot-password` | khách | Gửi email đặt lại; thông báo giống nhau dù email có tồn tại hay không |
| GET/POST `/admin/reset-password/{token}` | khách | Đặt mật khẩu mới; token hết hạn/đã dùng → báo lỗi |
| GET/POST `/admin/profile/password` | E | Tự đổi mật khẩu (nhập mật khẩu hiện tại) |
| GET `/admin/users` · GET/POST `/admin/users/new` · GET/POST `/admin/users/{id}` | A | Danh sách, tạo, sửa (họ tên, email, vai trò) |
| POST `/admin/users/{id}/lock` · `/unlock` | A | Khoá/mở khoá; không khoá được quản trị viên cuối cùng |

## Bảng điều khiển & cấu hình

| Phương thức + đường dẫn | Quyền | Mô tả |
|---|---|---|
| GET `/admin` | E | Bảng điều khiển (FR-021): mục "Thay đổi gần đây" chỉ gồm hành động nội dung, không IP, không đăng nhập/tài khoản |
| GET/POST `/admin/settings` | A | Thông tin chung (FR-022), gồm ngày tổ chức (sửa mốc `conference_day`) |
| GET/POST `/admin/menu` | A | Cây menu; POST nhận thứ tự dạng `items[][id,parent_id,sort_order]` |
| GET/POST `/admin/home` | E | Đoạn giới thiệu, slider, khối nổi bật |
| GET/POST `/admin/conferences` · POST `/admin/conferences/{id}/copy` · POST `/admin/conferences/{id}/make-current` | A | Danh sách hội thảo, sao chép sang năm mới, đặt làm hiện hành |
| POST `/admin/switch-conference` (trường `conference_id`) | E | Chọn hội thảo để sửa |

## Trang nội dung

| Phương thức + đường dẫn | Quyền | Mô tả |
|---|---|---|
| GET `/admin/pages?status=&tag=&q=` | E | Danh sách, lọc theo trạng thái, thẻ, từ khoá |
| GET/POST `/admin/pages/new` · GET/POST `/admin/pages/{id}` | E | Tạo/sửa; POST gửi kèm `version`; lệch → cảnh báo xung đột, không ghi |
| GET `/admin/pages/{id}/preview` | E | Xem trước với giao diện công khai + dải "Bản xem trước" |
| POST `/admin/pages/{id}/publish` · `/hide` · `/draft` | E | Đổi trạng thái; publish nhận `published_at` (rỗng = ngay) |
| GET `/admin/pages/{id}/revisions` · POST `/admin/pages/{id}/revisions/{rev}/restore` | E | Lịch sử và khôi phục |
| POST `/admin/pages/{id}/delete` | E | Nếu có mục menu trỏ tới trang: trả về trang xác nhận liệt kê các mục menu đó; xác nhận lần hai mới vào thùng rác (mục menu tự ẩn khỏi menu công khai) |

## Dữ liệu có cấu trúc

Mỗi nhóm có cùng mẫu: `GET /admin/{nhóm}` (danh sách), `GET/POST /admin/{nhóm}/new`, `GET/POST /admin/{nhóm}/{id}`,
`POST /admin/{nhóm}/{id}/toggle-visibility`, `POST /admin/{nhóm}/reorder` (`ids[]` theo thứ tự mới),
`POST /admin/{nhóm}/{id}/delete` (xác nhận trước ở giao diện → thùng rác). Quyền **E**.

| {nhóm} | Ghi chú |
|---|---|
| `dates` | Mốc thời gian |
| `committees` | Ban; trong trang sửa ban có danh sách thành viên `…/committees/{id}/members/...` |
| `committees/{id}/import` | GET form + link tệp mẫu tĩnh `/assets/templates/committee-import-template.xlsx` và `.csv`; POST nhận `.xlsx`/`.csv` (xem `import-format.md`) |
| `speakers` | Diễn giả chính |
| `program` | Ngày → phiên → mục (đường dẫn lồng `program/days/{id}/sessions/{id}/items/...`); `program/file` tải ảnh/PDF thay thế |
| `sponsors`, `sponsor-tiers` | Nhà tài trợ và hạng |
| `contacts` | Người liên hệ |

## Thư viện tệp, thùng rác, nhật ký

| Phương thức + đường dẫn | Quyền | Mô tả |
|---|---|---|
| GET `/admin/media?q=&kind=` | E | Lưới tệp, tìm theo tên |
| POST `/admin/media/upload` | E | Nhận một hoặc nhiều tệp JPG/PNG/WebP/GIF (≤ 5 MB), PDF/DOC/DOCX (≤ 20 MB); SVG và loại khác bị từ chối; lỗi nêu rõ giới hạn và các loại cho phép |
| GET `/admin/media/picker` | E | Hộp chọn tệp dùng trong trình soạn thảo và các biểu mẫu |
| POST `/admin/media/{id}/delete` | E | Nếu đang được dùng: trả về trang xác nhận liệt kê nơi dùng; xác nhận lần hai mới vào thùng rác |
| GET `/admin/trash` | E | Danh sách mục đã xoá, ngày tự xoá vĩnh viễn |
| POST `/admin/trash/{type}/{id}/restore` | E | Khôi phục; slug trùng → yêu cầu nhập slug khác |
| POST `/admin/trash/{type}/{id}/purge` | A | Xoá vĩnh viễn |
| GET `/admin/activity?user=&action=&from=&to=` | A | Nhật ký, chỉ đọc |
