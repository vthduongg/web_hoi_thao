# Contract: Đường dẫn công khai

Mọi phản hồi là HTML, `lang` theo `conferences.public_language` (hiện `en`). Chỉ phương thức `GET`.
Chuỗi giao diện lấy từ `lang/en.php`.

| Đường dẫn | Nội dung | Trả về |
|---|---|---|
| `/` | Trang chủ của hội thảo hiện hành | 200 |
| `/{slug}` | Trang nội dung công khai của hội thảo hiện hành | 200; slug cũ → **301** tới slug mới; không công khai/không tồn tại → **404** |
| `/past-conferences` | Danh sách hội thảo `has_been_current = 1 AND is_current = 0`, mới nhất trước | 200 |
| `/{year}` | Trang chủ của hội thảo lưu trữ | 200 + dải "This is an archived conference"; năm là hội thảo hiện hành → **301** về `/`; năm chưa từng hiện hành → **404** |
| `/{year}/{slug}` | Trang nội dung của hội thảo lưu trữ | như trên |
| `/uploads/...` | Tệp tĩnh (ảnh, PDF, DOCX) | 200; không thực thi script trong thư mục này |
| `/assets/...` | CSS, JS, font, ảnh giao diện | 200, cache dài |
| `/robots.txt`, `/sitemap.xml` | Chỉ liệt kê trang công khai của hội thảo hiện hành | 200 |

`{year}` = 4 chữ số; `{slug}` = `[a-z0-9-]+`.

Trang lỗi 404/500 ngoài `/admin` dùng `templates/public/404.php`, `templates/public/500.php` bằng ngôn ngữ công khai.
Header CSP công khai theo research.md R18; `/uploads` có thêm `X-Content-Type-Options: nosniff`.

**"Related pages"**: tối đa 6 trang công khai khác trang hiện tại — trước hết các trang chung ít nhất một thẻ, sau đó
các trang cùng nhóm menu (nhóm = mục menu cấp 1 chứa mục đầu tiên trỏ tới trang); trang không có trong menu chỉ xét thẻ.

## Thành phần bắt buộc trên mọi trang (FR-012–FR-014)

- `<title>`: `seo_title` (hoặc `title`) + ` | ` + tên hội thảo; trang chủ chỉ có tên hội thảo.
- `<meta name="description">`, `og:title`, `og:description`, `og:image`, `og:url`, `twitter:card`.
- Header: logo (link `/`), menu có menu con mở được bằng chuột, chạm và bàn phím (Enter/Space/Esc);
  dưới 900 px menu thu vào nút "Menu".
- Footer: điện thoại (`tel:`), email (`mailto:`), nội dung footer, bản quyền, link "Past Conferences".
- Trang nội dung (không phải trang chủ): khối "Related pages" (tối đa 6) và nút chia sẻ Facebook, X, LinkedIn,
  "Copy link" — là link thường, không nhúng SDK bên ngoài.
- Liên kết "Skip to content" đầu trang.

## Khối dữ liệu theo `pages.module`

| module | Hiển thị dưới nội dung trang |
|---|---|
| `important_dates` | Bảng mốc: ngày `15 March 2026`; `extended` → `<del>ngày cũ</del> ngày mới` + nhãn "Extended"; `tba` → "To be announced"; mốc sắp tới có class `dates-table__row--upcoming` và nhãn "Upcoming" |
| `committees` | Mỗi ban gắn với trang: tiêu đề `<h2>` + bảng cột No. / Name / Position & Affiliation / Role |
| `keynote_speakers` | Lưới thẻ: ảnh, học hàm + họ tên, đơn vị, quốc gia, tiêu đề bài nói, tiểu sử |
| `program` | Bảng theo ngày → phiên → mục; hoặc ảnh/PDF thay thế; hoặc "Information will be updated soon." |
| `sponsors` | Nhóm theo hạng, logo có `alt` = tên đơn vị, link mở tab mới |
| `contacts` | Email chung + thẻ người liên hệ có `mailto:`/`tel:` |

Danh sách rỗng ở mọi module → "Information will be updated soon."

## Nút nộp bài

- Có `submission_url` → `<a class="button button--primary" href="…" target="_blank" rel="noopener">Submit your paper</a>`.
- Không có → nút bị vô hiệu với chữ "Submission opening soon" (`aria-disabled="true"`).
