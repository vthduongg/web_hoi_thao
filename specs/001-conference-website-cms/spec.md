# Feature Specification: Website hội thảo khoa học quốc tế kèm trang quản trị nội dung

**Feature Branch**: `001-conference-website-cms`

**Created**: 2026-11-08

**Status**: Draft

**Input**: User description: "Xây dựng website giới thiệu hội thảo khoa học quốc tế (mẫu tham chiếu:
VietESL International Conference 2025 của Trường ĐHSP Hà Nội 2), ngôn ngữ hiển thị tiếng Anh, giúp
tác giả, đại biểu và nhà tài trợ tìm được mọi thông tin cần thiết để nộp bài và tham dự, đồng thời
cho ban tổ chức tự cập nhật nội dung mà không cần lập trình viên." (mô tả đầy đủ gồm danh sách trang
công khai, 16 nhóm chức năng quản trị, phân quyền, yêu cầu chất lượng và phạm vi loại trừ)

## Clarifications

### Session 2026-11-08

- Q: Biên tập viên có được tự xuất bản hay phải chờ quản trị viên duyệt? → A: Biên tập viên tự
  xuất bản, ẩn và hẹn giờ như quản trị viên; không có bước duyệt.
- Q: Dữ liệu có cấu trúc (diễn giả, nhà tài trợ, mốc, thành viên ban, slider) có trạng thái riêng
  không? → A: Mỗi mục có công tắc Hiện/Ẩn (mặc định Hiện); lưu là áp dụng ngay.
- Q: Khách có xem được website hội thảo các năm trước không? → A: Có, công khai qua đường dẫn có năm
  (`/2025/...`), có trang "Past Conferences" liệt kê các năm và dải thông báo lưu trữ trên trang cũ.
- Q: Sao chép hội thảo sang năm mới có gồm diễn giả, nhà tài trợ, người liên hệ, chương trình không?
  → A: Gồm nhà tài trợ và người liên hệ; không gồm diễn giả và chương trình; tệp dùng chung, không nhân bản.
- Q: Thẻ (tags) của trang dùng để làm gì trên trang công khai? → A: Lọc/tìm trong trang quản trị và chọn
  trang cho khối "Related pages" (ưu tiên cùng thẻ, rồi cùng nhóm menu); không có trang danh sách theo thẻ.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Tác giả tìm thông tin và đi tới nơi nộp bài (Priority: P1)

Một nhà nghiên cứu mở website, thấy ngay tên hội thảo, chủ đề, ngày tổ chức, địa điểm và các hạn nộp
trên trang chủ. Họ vào Call for Papers để đọc chủ đề/tiểu ban, vào Important Dates để xem các mốc,
vào Paper Submission để đọc hướng dẫn định dạng, rồi bấm nút nộp bài để mở biểu mẫu nộp bài bên ngoài.

**Why this priority**: Thu hút bài nộp là mục đích chính của website; không có luồng này thì website
không có giá trị.

**Independent Test**: Với dữ liệu mẫu có sẵn, một người chưa từng xem website tìm được hạn nộp tóm tắt
và mở được biểu mẫu nộp bài chỉ từ trang chủ, không cần trợ giúp.

**Acceptance Scenarios**:

1. **Given** website đã có thông tin chung và mốc thời gian, **When** khách mở trang chủ, **Then** họ
   thấy tên hội thảo, chủ đề, ngày tổ chức, địa điểm, ngôn ngữ, hạn nộp tóm tắt, hạn nộp toàn văn và
   lối tắt tới Call for Papers và Paper Submission.
2. **Given** quản trị viên đã cấu hình link nộp bài, **When** khách bấm nút nộp bài ở Call for Papers,
   Paper Submission hoặc trang chủ, **Then** biểu mẫu nộp bài bên ngoài mở trong tab mới.
3. **Given** hạn nộp tóm tắt đã được gia hạn, **When** khách xem Important Dates, **Then** ngày cũ hiện
   gạch ngang cạnh ngày mới kèm nhãn "Extended".
4. **Given** có mốc chưa đến hạn, **When** khách xem Important Dates, **Then** mốc gần nhất sắp tới được
   làm nổi bật khác các mốc còn lại.
5. **Given** một mốc ở trạng thái "sẽ thông báo sau", **When** khách xem mốc đó, **Then** thay cho ngày
   là chữ "To be announced".

---

### User Story 2 - Ban tổ chức tự cập nhật trang nội dung (Priority: P1)

Biên tập viên đăng nhập trang quản trị (tiếng Việt), mở trang Paper Submission, sửa nội dung bằng trình
soạn thảo trực quan, xem trước, rồi xuất bản. Nội dung mới hiển thị ngay trên trang công khai.

**Why this priority**: Yêu cầu cốt lõi là ban tổ chức không phụ thuộc lập trình viên; thiếu luồng này
thì mọi thay đổi nội dung đều phải nhờ kỹ thuật.

**Independent Test**: Một người không chuyên kỹ thuật, chỉ được hướng dẫn ngắn, sửa một đoạn văn và
thêm một tệp PDF đính kèm vào một trang rồi xuất bản trong dưới 5 phút; trang công khai phản ánh ngay.

**Acceptance Scenarios**:

1. **Given** biên tập viên đã đăng nhập, **When** họ sửa nội dung một trang và bấm "Xuất bản", **Then**
   khách tải lại trang công khai thấy nội dung mới mà không cần triển khai lại website.
2. **Given** một trang ở trạng thái Bản nháp, **When** khách truy cập đường dẫn của trang, **Then** khách
   nhận trang "Page not found"; người đã đăng nhập dùng "Xem trước" thì thấy được nội dung.
3. **Given** trang được hẹn giờ xuất bản lúc 08:00, **When** đến 08:00, **Then** trang tự hiển thị công khai.
4. **Given** trang có nhiều phiên bản đã lưu, **When** biên tập viên chọn khôi phục một bản cũ, **Then**
   nội dung bản cũ trở thành phiên bản mới nhất và bản hiện tại vẫn còn trong lịch sử.
5. **Given** biên tập viên đặt đường dẫn thân thiện đã được trang khác dùng, **When** họ lưu, **Then**
   hệ thống báo lỗi tiếng Việt và không lưu.

---

### User Story 3 - Quản lý dữ liệu có cấu trúc dùng chung (Priority: P2)

Biên tập viên cập nhật mốc thời gian, thành viên các ban, diễn giả chính, chương trình, nhà tài trợ và
người liên hệ qua các biểu mẫu riêng. Mỗi dữ liệu chỉ nhập một lần, mọi trang công khai dùng chung.

**Why this priority**: Đảm bảo thông tin nhất quán (không lệch ngày giữa trang chủ và Important Dates)
và giúp cập nhật nhanh, nhưng website vẫn dùng được với trang nội dung tự do nếu chưa có.

**Independent Test**: Đổi hạn nộp tóm tắt một lần trong trang quản trị, kiểm tra trang chủ, Important
Dates và Call for Papers đều hiện ngày mới.

**Acceptance Scenarios**:

1. **Given** hạn nộp tóm tắt hiển thị ở trang chủ và Important Dates, **When** biên tập viên đổi ngày
   và đánh dấu "gia hạn", **Then** cả hai trang hiện ngày mới và ngày cũ gạch ngang.
2. **Given** biên tập viên có tệp Excel/CSV danh sách 30 thành viên đúng mẫu, **When** họ nhập vào
   Hội đồng khoa học, **Then** 30 thành viên được thêm đúng thứ tự trong tệp.
3. **Given** tệp nhập có dòng thiếu họ tên, **When** nhập, **Then** hệ thống liệt kê từng dòng lỗi
   bằng tiếng Việt và không thêm bất kỳ thành viên nào cho đến khi tệp hợp lệ.
4. **Given** trang Organizing Committee gắn hai ban (Ban chỉ đạo, Ban tổ chức), **When** khách xem,
   **Then** mỗi ban là một bảng riêng có tiêu đề, cột STT, học hàm/học vị + họ tên, chức vụ và đơn vị, vai trò.
5. **Given** chương trình chưa có dữ liệu chi tiết nhưng đã có tệp PDF, **When** khách xem Program,
   **Then** họ thấy tệp PDF (xem hoặc tải về); khi có dữ liệu phiên, trang hiện bảng theo ngày và phiên.
6. **Given** nhà tài trợ thuộc các hạng khác nhau, **When** khách xem Sponsor, **Then** logo được nhóm
   theo hạng, đúng thứ tự, mỗi logo có link tới website đơn vị (nếu có).

---

### User Story 4 - Đại biểu chuẩn bị tham dự (Priority: P2)

Đại biểu xem chương trình, diễn giả chính, thông tin đi lại (bản đồ, khoảng cách tới sân bay/trung tâm,
phương tiện, điểm tham quan) và liên hệ ban tổ chức bằng cách bấm email/số điện thoại trên điện thoại di động.

**Why this priority**: Cần cho giai đoạn gần hội thảo; sau luồng nộp bài.

**Independent Test**: Trên điện thoại, đại biểu mở Contact và bấm số điện thoại thì ứng dụng gọi điện mở
với số đó; bấm email thì ứng dụng thư mở với địa chỉ đó.

**Acceptance Scenarios**:

1. **Given** có diễn giả chính, **When** khách mở Keynote Speaker, **Then** mỗi diễn giả là một thẻ có
   ảnh, họ tên, đơn vị, tiêu đề bài nói, tiểu sử ngắn, đúng thứ tự hiển thị.
2. **Given** khách dùng điện thoại, **When** mở bất kỳ trang nào, **Then** menu thu gọn thành nút mở menu,
   nội dung không bị tràn ngang.
3. **Given** trang Travel Info có bản đồ và video nhúng, **When** khách xem, **Then** bản đồ và video hiển
   thị trong trang và co giãn theo màn hình.

---

### User Story 5 - Đăng nhập, tài khoản và phân quyền (Priority: P1)

Quản trị viên tạo tài khoản cho biên tập viên. Người dùng đăng nhập bằng email + mật khẩu, quên mật khẩu
thì đặt lại qua email. Biên tập viên không thấy và không vào được các chức năng chỉ dành cho quản trị viên.

**Why this priority**: Là điều kiện tiên quyết của mọi chức năng quản trị và của an toàn website.

**Independent Test**: Đăng nhập bằng tài khoản biên tập viên, thử mở trực tiếp đường dẫn quản lý tài khoản
và nhật ký; cả hai đều bị từ chối.

**Acceptance Scenarios**:

1. **Given** chưa đăng nhập, **When** truy cập bất kỳ đường dẫn quản trị nào, **Then** bị chuyển tới trang đăng nhập.
2. **Given** nhập sai mật khẩu 5 lần liên tiếp, **When** thử lần thứ 6, **Then** tài khoản bị khóa tạm
   15 phút và có thông báo tiếng Việt.
3. **Given** đăng nhập nhưng không thao tác 30 phút, **When** thao tác tiếp, **Then** phải đăng nhập lại.
4. **Given** quên mật khẩu, **When** yêu cầu đặt lại, **Then** nhận email có liên kết dùng một lần, hết
   hạn sau 60 phút; thông báo trên màn hình giống nhau dù email có tồn tại hay không.
5. **Given** quản trị viên khóa một tài khoản, **When** người đó đăng nhập, **Then** bị từ chối; phiên
   đang mở của người đó cũng chấm dứt.
6. **Given** đăng nhập là biên tập viên, **When** xem menu quản trị, **Then** không có mục Tài khoản,
   Thông tin chung, Menu, Nhật ký, Sao chép hội thảo, Xóa vĩnh viễn.

---

### User Story 6 - Cấu hình chung, menu và trang chủ (Priority: P2)

Quản trị viên sửa thông tin chung (tên, chủ đề, năm, ngày, địa điểm, logo, favicon, liên hệ, footer, link
nộp bài, mạng xã hội), sắp xếp menu bằng kéo thả, và quản lý slider, đoạn giới thiệu, khối nổi bật ở trang chủ.

**Why this priority**: Cho phép thay đổi khung website không cần lập trình viên; tần suất thấp hơn sửa nội dung.

**Independent Test**: Đổi email liên hệ trong Thông tin chung; footer mọi trang và trang Contact hiện email mới.

**Acceptance Scenarios**:

1. **Given** quản trị viên kéo mục "Program" lên trước "Conference", **When** lưu, **Then** menu công khai
   hiện theo thứ tự mới.
2. **Given** một mục menu con trỏ tới link ngoài, **When** khách bấm, **Then** link mở trong tab mới.
3. **Given** một ảnh slider bị tắt, **When** khách mở trang chủ, **Then** ảnh đó không xuất hiện.

---

### User Story 7 - Thư viện tệp, thùng rác, nhật ký (Priority: P3)

Người dùng quản trị tải lên, tìm kiếm và xóa ảnh/tài liệu; khôi phục nội dung xóa nhầm từ thùng rác trong
30 ngày; quản trị viên tra cứu nhật ký ai đã làm gì, lúc nào.

**Why this priority**: Tăng an toàn và khả năng truy vết, nhưng không chặn luồng chính.

**Independent Test**: Xóa một diễn giả, vào thùng rác khôi phục; diễn giả hiện lại đúng vị trí; nhật ký
có hai dòng tương ứng.

**Acceptance Scenarios**:

1. **Given** một ảnh đang được dùng ở trang Travel Info, **When** người dùng xóa ảnh, **Then** hệ thống
   cảnh báo nơi đang dùng và yêu cầu xác nhận.
2. **Given** tải lên tệp vượt giới hạn dung lượng hoặc sai loại, **When** tải, **Then** bị từ chối với
   thông báo nêu rõ giới hạn và các loại cho phép.
3. **Given** nội dung đã ở thùng rác quá 30 ngày, **When** hết hạn, **Then** nội dung bị xóa vĩnh viễn tự động.
4. **Given** biên tập viên ở thùng rác, **When** xem một mục, **Then** chỉ có nút "Khôi phục", không có
   "Xóa vĩnh viễn".

---

### User Story 8 - Sao chép hội thảo sang năm mới (Priority: P3)

Quản trị viên nhân bản hội thảo năm nay thành hội thảo năm sau (trang, menu, ban, mốc thời gian), chỉnh
lại tên, ngày, chủ đề rồi chọn làm hội thảo đang hiển thị. Hội thảo cũ vẫn được lưu trữ và xem lại được.

**Why this priority**: Cần mỗi năm một lần; giá trị dài hạn nhưng không cần cho lần ra mắt đầu tiên.

**Independent Test**: Sao chép hội thảo 2025 thành 2026; sửa một trang của 2026 không làm thay đổi trang
tương ứng của 2025.

**Acceptance Scenarios**:

1. **Given** hội thảo 2025 đầy đủ nội dung, **When** sao chép thành 2026, **Then** bản 2026 có cùng cấu
   trúc trang, menu, ban, mốc thời gian, nhà tài trợ, người liên hệ; các trang ở trạng thái Bản nháp;
   danh sách diễn giả chính và chương trình trống.
2. **Given** bản 2026 được đặt là hội thảo hiện hành, **When** khách mở trang chủ, **Then** thấy nội dung
   2026; nội dung 2025 vẫn truy cập được qua đường dẫn `/2025/...`, có dải thông báo lưu trữ, và
   được liệt kê ở trang "Past Conferences".

---

### Edge Cases

- Chưa cấu hình link nộp bài: nút nộp bài hiển thị trạng thái "Submission opening soon" và không bấm được.
- Mọi mốc đã qua: không mốc nào được làm nổi bật là "sắp tới"; mốc ngày hội thảo vẫn hiển thị bình thường.
- Mốc "sẽ thông báo sau" không được tính là mốc sắp tới.
- Hai người cùng sửa một trang: người lưu sau được cảnh báo trang đã bị thay đổi và có thể xem bản kia
  trước khi ghi đè; không mất dữ liệu nào vì mọi lần lưu đều vào lịch sử phiên bản.
- Ảnh thiếu mô tả thay thế: hệ thống nhắc nhập mô tả trước khi xuất bản.
- Ban không có thành viên hoặc trang không có nội dung: trang công khai hiện thông báo "Information will
  be updated soon." thay vì bảng/khối trống.
- Xóa một trang đang được menu trỏ tới: cảnh báo trước khi xóa; mục menu đó tự ẩn khỏi menu công khai.
- Đổi đường dẫn thân thiện của trang đã xuất bản: đường dẫn cũ tự chuyển hướng sang đường dẫn mới.
- Quản trị viên cuối cùng không thể tự khóa hoặc hạ vai trò của mình.
- Khôi phục từ thùng rác khi đường dẫn đã bị trang khác dùng: yêu cầu đặt đường dẫn khác.
- Tệp nhập Excel/CSV sai mẫu cột: báo lỗi chỉ rõ cột thiếu và cung cấp tệp mẫu để tải về.

## Requirements *(mandatory)*

### Functional Requirements

**Trang công khai (văn bản hiển thị tiếng Anh)**

- **FR-001**: Trang chủ MUST hiển thị slider ảnh (chỉ các ảnh đang bật, đúng thứ tự), tên hội thảo, chủ đề,
  đoạn giới thiệu, thông tin chính (ngày tổ chức, địa điểm, ngôn ngữ, hạn nộp tóm tắt, hạn nộp toàn văn)
  và lối tắt tới Call for Papers và Paper Submission.
- **FR-002**: Thông tin chính và các hạn nộp trên mọi trang MUST lấy từ một nguồn dữ liệu duy nhất
  (Thông tin chung và Mốc thời gian), không nhập lặp.
- **FR-003**: Website MUST có các trang: Call for Papers, Important Dates, Scientific Committee,
  Organizing Committee, Keynote Speaker (nằm trong menu Conference), Program, Paper Submission, Sponsor,
  Contact, Travel Info; quản trị viên có thể thêm trang khác.
- **FR-004**: Important Dates MUST hiển thị bảng mốc theo thứ tự đã sắp xếp; mốc gia hạn hiện ngày cũ gạch
  ngang và ngày mới; mốc "sẽ thông báo sau" hiện "To be announced"; mốc sắp tới gần nhất được làm nổi bật.
- **FR-005**: Trang ban/hội đồng MUST hiển thị một hoặc nhiều ban, mỗi ban là một bảng gồm STT, học hàm/học
  vị + họ tên, chức vụ và đơn vị, vai trò; ảnh thành viên hiện nếu có.
- **FR-006**: Keynote Speaker MUST hiển thị thẻ diễn giả gồm ảnh, họ tên kèm học hàm, đơn vị, quốc gia,
  tiêu đề bài nói, tiểu sử ngắn.
- **FR-007**: Program MUST hiển thị chương trình theo ngày → phiên → mục (giờ bắt đầu–kết thúc, nội dung,
  người trình bày, phòng/tiểu ban); nếu không có dữ liệu chi tiết thì hiển thị ảnh/PDF chương trình đã tải lên.
- **FR-008**: Nút nộp bài MUST mở link biểu mẫu bên ngoài do quản trị viên cấu hình, trong tab mới.
- **FR-009**: Sponsor MUST hiển thị logo và tên đơn vị nhóm theo hạng, đúng thứ tự, có link tới website đơn vị nếu có.
- **FR-010**: Contact MUST hiển thị email chung và danh sách người liên hệ; email và số điện thoại MUST
  bấm được để mở ứng dụng thư/gọi điện.
- **FR-011**: Travel Info MUST hỗ trợ văn bản, bản đồ nhúng, video nhúng và các khối điểm tham quan (ảnh + mô tả).
- **FR-012**: Mọi trang MUST có header (logo, menu có menu thả xuống) và footer (điện thoại, email, bản quyền
  lấy từ Thông tin chung).
- **FR-013**: Mỗi trang nội dung MUST có khối "Related pages" liên kết tới tối đa 6 trang đã xuất bản (trừ
  trang hiện tại): trước hết các trang có chung ít nhất một thẻ, sau đó các trang cùng nhóm menu; khối ẩn nếu
  không có trang nào. "Nhóm menu" của một trang là mục menu cấp 1 chứa mục đầu tiên (theo thứ tự menu) trỏ tới
  trang đó; trang không có trong menu chỉ xét các trang cùng thẻ. Mỗi trang cũng MUST có nút chia sẻ lên Facebook, X, LinkedIn và sao chép link.
- **FR-014**: Mỗi trang công khai MUST có tiêu đề và mô tả riêng cho tìm kiếm và chia sẻ, cùng ảnh chia sẻ
  (dùng ảnh đại diện của trang, nếu không có thì dùng logo).
- **FR-015**: Trang không tồn tại, bản nháp, bị ẩn hoặc chưa đến giờ xuất bản MUST trả về trang "Page not
  found" thân thiện cho khách.

**Tài khoản và bảo mật (giao diện quản trị tiếng Việt)**

- **FR-016**: Hệ thống MUST cho đăng nhập bằng email + mật khẩu, đăng xuất, đặt lại mật khẩu qua email
  (liên kết dùng một lần, hết hạn sau 60 phút), tự đổi mật khẩu (phải nhập mật khẩu hiện tại).
- **FR-017**: Hệ thống MUST khóa tạm tài khoản 15 phút sau 5 lần đăng nhập sai liên tiếp và MUST kết thúc
  phiên sau 30 phút không hoạt động.
- **FR-018**: Mật khẩu MUST dài tối thiểu 8 ký tự và MUST được lưu dưới dạng không thể khôi phục ngược.
- **FR-019**: Quản trị viên MUST tạo, sửa, khóa/mở khóa tài khoản và gán vai trò Quản trị viên hoặc Biên tập viên;
  hệ thống MUST luôn giữ ít nhất một quản trị viên đang hoạt động.
- **FR-020**: Hệ thống MUST kiểm tra quyền ở mọi thao tác quản trị (không chỉ ẩn nút); biên tập viên MUST bị
  từ chối khi truy cập trực tiếp chức năng tài khoản, thông tin chung, menu, nhật ký, sao chép/đặt hiện hành hội
  thảo, xóa vĩnh viễn. Biên tập viên MUST được dùng: bảng điều khiển, trang nội dung, trang chủ (slider, giới thiệu,
  khối nổi bật), mốc thời gian, ban và thành viên, diễn giả, chương trình, nhà tài trợ, người liên hệ, thư viện tệp,
  khôi phục từ thùng rác, chọn hội thảo để sửa, tự đổi mật khẩu.

**Quản lý nội dung**

- **FR-021**: Bảng điều khiển MUST hiển thị số trang đã xuất bản và bản nháp, 5 mốc thời gian sắp tới,
  10 thay đổi nội dung gần nhất và lối tắt tới các mục hay sửa. "Thay đổi nội dung" chỉ gồm tạo, sửa, xóa,
  khôi phục, xuất bản, ẩn nội dung của hội thảo đang sửa, mỗi dòng gồm người thực hiện, hành động, tên đối
  tượng, thời điểm; KHÔNG hiển thị địa chỉ IP, sự kiện đăng nhập/đăng xuất hay thay đổi tài khoản. Màn hình
  Nhật ký đầy đủ (FR-037) vẫn chỉ quản trị viên xem được.
- **FR-022**: Thông tin chung MUST gồm tên, chủ đề, năm, ngày tổ chức, địa điểm, ngôn ngữ, logo, favicon,
  email, điện thoại, nội dung footer, link nộp bài, link mạng xã hội; thay đổi MUST áp dụng cho mọi trang công khai.
- **FR-023**: Trình soạn thảo trực quan MUST hỗ trợ tiêu đề, đoạn văn, danh sách, bảng, ảnh, video nhúng,
  tệp đính kèm (PDF, Word), liên kết; người dùng không cần viết mã.
- **FR-024**: Mỗi trang nội dung MUST có đường dẫn thân thiện (duy nhất), tiêu đề và mô tả chia sẻ, ảnh đại diện, thẻ.
  Thẻ dùng để lọc/tìm trang trong trang quản trị và chọn trang cho khối "Related pages"; thẻ không hiển thị
  cho khách và không có trang danh sách theo thẻ.
- **FR-025**: Mỗi trang MUST có trạng thái Bản nháp / Xuất bản / Ẩn, hỗ trợ hẹn giờ xuất bản và xem trước.
  Cả quản trị viên và biên tập viên đều được chuyển trạng thái, xuất bản và hẹn giờ; không có bước duyệt.
- **FR-026**: Mỗi lần lưu trang MUST tạo một phiên bản (người sửa, thời điểm); người dùng MUST xem và khôi
  phục được bất kỳ phiên bản cũ nào.
- **FR-027**: Nội dung đã xuất bản MUST hiển thị trên trang công khai ngay sau khi xuất bản, không cần triển khai lại.
- **FR-028**: Quản lý menu MUST cho thêm, sửa, xóa, sắp xếp mục và mục con (tối đa 2 cấp) bằng kéo thả; mục
  trỏ tới trang trong site hoặc link ngoài.
- **FR-029**: Quản lý trang chủ MUST cho quản lý ảnh slider (ảnh, tiêu đề, link, thứ tự, bật/tắt), đoạn giới
  thiệu và các khối thông tin nổi bật.
- **FR-030**: Quản lý mốc thời gian MUST cho thêm, sửa, xóa, sắp xếp; mỗi mốc có tên, ngày, trạng thái
  (bình thường / gia hạn kèm ngày cũ / sẽ thông báo sau) và có thể đánh dấu là "hạn nộp tóm tắt",
  "hạn nộp toàn văn" hoặc "ngày hội thảo" — đây là nguồn dữ liệu duy nhất nêu ở FR-002.
- **FR-031**: Quản lý ban MUST cho tạo nhiều ban, quản lý thành viên (học hàm/học vị, họ tên, chức vụ và
  đơn vị, vai trò, ảnh tùy chọn), sắp xếp, gắn ban vào trang, và nhập hàng loạt từ Excel/CSV theo tệp mẫu
  tải về được; nhập theo nguyên tắc tất cả hoặc không.
- **FR-032**: Quản lý diễn giả chính MUST gồm ảnh, họ tên, học hàm, đơn vị, quốc gia, tiêu đề bài nói,
  tiểu sử, thứ tự hiển thị.
- **FR-033**: Quản lý chương trình MUST cho tạo ngày, phiên, mục trong phiên, hoặc tải lên ảnh/PDF thay thế.
- **FR-034**: Quản lý nhà tài trợ MUST gồm logo, tên, link website, hạng (đơn vị tổ chức, phối hợp, tài trợ
  và hạng tự đặt), thứ tự.
- **FR-035**: Quản lý người liên hệ MUST gồm họ tên, đơn vị, email, điện thoại, thứ tự; email và số điện
  thoại MUST được kiểm tra định dạng.
- **FR-035a**: Mỗi mục dữ liệu có cấu trúc (mốc thời gian, ban, thành viên ban, diễn giả, mục chương trình,
  nhà tài trợ, người liên hệ, ảnh slider, khối nổi bật) MUST có công tắc Hiện/Ẩn, mặc định Hiện; thay đổi
  áp dụng lên trang công khai ngay khi lưu; mục Ẩn không xuất hiện ở bất kỳ trang công khai nào. Các mục này
  không có bản nháp, hẹn giờ hay lịch sử phiên bản.
- **FR-036**: Thư viện tệp MUST cho tải lên, xem, tìm theo tên, xóa; chỉ chấp nhận ảnh (JPG, PNG, WebP, GIF)
  tối đa 5 MB và tài liệu (PDF, DOC, DOCX) tối đa 20 MB; KHÔNG chấp nhận SVG (có thể chứa mã thực thi); tự tạo ảnh thu nhỏ; cảnh báo kèm danh sách
  nơi sử dụng khi xóa tệp đang được dùng.
- **FR-037**: Hệ thống MUST ghi nhật ký mọi thao tác tạo, sửa, xóa, khôi phục, xuất bản, nhập tệp, sao chép
  hội thảo, đăng nhập, thay đổi tài khoản (ai, làm gì, trên nội dung nào, lúc nào, từ IP nào); màn hình Nhật ký
  đầy đủ chỉ quản trị viên xem và lọc được (bảng điều khiển chỉ hiện phần tóm tắt nêu ở FR-021); nhật ký không
  sửa/xóa được.
- **FR-038**: Quản trị viên MUST sao chép được một hội thảo thành hội thảo mới (trang, menu, ban kèm thành
  viên, mốc thời gian, cấu hình trang chủ, thông tin chung, nhà tài trợ kèm hạng, người liên hệ); diễn giả
  chính và chương trình KHÔNG được sao chép; tệp trong thư viện được dùng chung, không nhân bản. Quản trị viên
  chọn một hội thảo làm hiện hành; các hội thảo cũ giữ lại.
- **FR-038a**: Hội thảo hiện hành MUST dùng đường dẫn chính (không có năm). Mỗi hội thảo không hiện hành đã
  từng được đặt làm hiện hành MUST xem được công khai qua đường dẫn có năm (ví dụ `/2025/call-for-papers`),
  chỉ gồm nội dung đã xuất bản/Hiện; mọi trang lưu trữ MUST có dải thông báo "This is an archived conference"
  kèm link tới hội thảo hiện hành. Website MUST có trang "Past Conferences" liệt kê các năm lưu trữ, liên kết
  từ footer. Hội thảo mới sao chép nhưng chưa từng làm hiện hành MUST không truy cập công khai được.
- **FR-039**: Mọi thao tác xóa MUST yêu cầu xác nhận; nội dung bị xóa vào thùng rác, khôi phục được trong
  30 ngày, sau đó tự xóa vĩnh viễn; chỉ quản trị viên được xóa vĩnh viễn thủ công.
- **FR-040**: Mọi thông báo, nhãn, lỗi trong trang quản trị MUST bằng tiếng Việt; ngày giờ trong trang quản
  trị theo định dạng `dd/mm/yyyy` 24h, múi giờ Asia/Ho_Chi_Minh. Trang lỗi (404, 403, 419, 500) dưới `/admin`
  MUST bằng tiếng Việt; trang lỗi ở mọi đường dẫn khác MUST bằng ngôn ngữ công khai của hội thảo. Ngoại lệ duy
  nhất: dải "Bản xem trước" (tiếng Việt) hiện trên trang công khai khi người quản trị xem trước — khách không bao
  giờ thấy dải này.

**Chất lượng**

- **FR-041**: Trang công khai MUST hiển thị đúng ở chiều rộng từ 360 px (điện thoại) tới màn hình máy tính;
  menu thu gọn trên màn hình nhỏ; trang quản trị MUST dùng được trên máy tính bảng và máy tính.
- **FR-042**: Website MUST đáp ứng tiếp cận cơ bản: ảnh có mô tả thay thế, tương phản chữ tối thiểu 4.5:1,
  mọi chức năng (kể cả menu thả xuống) dùng được bằng bàn phím.
- **FR-043**: Dữ liệu và tệp MUST được sao lưu tự động hằng ngày, giữ tối thiểu 30 bản gần nhất, và khôi
  phục được.

### Key Entities *(include if feature involves data)*

- **Hội thảo (Conference)**: một kỳ hội thảo theo năm; chứa thông tin chung (tên, chủ đề, năm, ngày, địa
  điểm, ngôn ngữ, logo, favicon, liên hệ, footer, link nộp bài, mạng xã hội); một kỳ được đặt là hiện hành.
  Mọi nội dung khác thuộc về một hội thảo.
- **Trang nội dung (Page)**: tiêu đề, nội dung, đường dẫn, tiêu đề/mô tả chia sẻ, ảnh đại diện, thẻ, trạng
  thái, thời điểm xuất bản; có nhiều Phiên bản; có thể gắn Ban, Chương trình.
- **Phiên bản trang (Page Revision)**: bản chụp nội dung trang, người lưu, thời điểm.
- **Mục menu (Menu Item)**: nhãn, đích (trang hoặc link ngoài), thứ tự, mục cha.
- **Ảnh slider / Khối nổi bật (Slide / Highlight)**: ảnh, tiêu đề, link, thứ tự, bật/tắt.
- **Mốc thời gian (Important Date)**: tên, ngày, ngày cũ (khi gia hạn), trạng thái, loại đặc biệt, thứ tự.
- **Ban (Committee)** và **Thành viên ban (Committee Member)**: ban có tên, thứ tự; thành viên có học hàm/học
  vị, họ tên, chức vụ và đơn vị, vai trò, ảnh, thứ tự.
- **Diễn giả chính (Keynote Speaker)**: ảnh, họ tên, học hàm, đơn vị, quốc gia, tiêu đề bài nói, tiểu sử, thứ tự.
- **Chương trình (Program)**: Ngày → Phiên → Mục (giờ bắt đầu/kết thúc, nội dung, người trình bày, phòng);
  hoặc tệp ảnh/PDF thay thế.
- **Nhà tài trợ (Sponsor)** và **Hạng (Sponsor Tier)**: logo, tên, link, hạng, thứ tự.
- **Người liên hệ (Contact Person)**: họ tên, đơn vị, email, điện thoại, thứ tự.
- **Tệp (Media File)**: tên, loại, dung lượng, ảnh thu nhỏ, mô tả thay thế, nơi đang sử dụng.
- **Tài khoản (User)**: email, họ tên, vai trò, trạng thái hoạt động/khóa, số lần đăng nhập sai.
- **Nhật ký (Activity Log)**: người thực hiện, hành động, đối tượng, thời điểm.
- **Mục thùng rác (Trash Item)**: nội dung bị xóa, người xóa, thời điểm xóa, hạn xóa vĩnh viễn.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Người lần đầu vào website tìm được hạn nộp tóm tắt trong 30 giây và mở được biểu mẫu nộp
  bài trong tối đa 3 lần bấm từ trang chủ.
- **SC-002**: Trang công khai hiển thị nội dung chính dưới 3 giây trên kết nối 4G thông thường cho 95% lượt truy cập.
- **SC-003**: 90% người không chuyên kỹ thuật (sau hướng dẫn ngắn dưới 15 phút) cập nhật và xuất bản xong
  một trang nội dung trong 5 phút.
- **SC-004**: Nội dung xuất bản hiển thị trên trang công khai trong vòng 1 phút, không cần lập trình viên.
- **SC-005**: Mỗi mốc thời gian chỉ nhập ở một nơi; kiểm tra toàn website cho thấy 0 trường hợp ngày bị lệch giữa các trang.
- **SC-006**: Dựng website cho năm mới từ bản sao chép trong một ngày làm việc, không cần lập trình viên.
- **SC-007**: 100% đường dẫn quản trị từ chối truy cập khi chưa đăng nhập; 100% chức năng chỉ dành cho quản
  trị viên từ chối biên tập viên.
- **SC-008**: Mọi trang công khai đạt kiểm tra tiếp cận cơ bản (mô tả ảnh, tương phản, bàn phím) không có lỗi nghiêm trọng.
- **SC-009**: 100% nội dung xóa nhầm khôi phục được trong 30 ngày; dữ liệu khôi phục được từ bản sao lưu
  không cũ hơn 24 giờ.
- **SC-010**: Ban tổ chức không cần nhờ lập trình viên cho bất kỳ thay đổi nội dung nào trong suốt mùa hội thảo.

## Assumptions

- Văn bản trang công khai chỉ bằng tiếng Anh (ngôn ngữ của hội thảo); trang quản trị chỉ bằng tiếng Việt —
  đúng Nguyên tắc III của constitution v2.0.0. Đa ngôn ngữ ngoài phạm vi.
- Logo và ảnh cần tải lên dạng PNG/WebP/JPG; SVG bị loại vì có thể chứa script gây lỗ hổng XSS khi phục vụ
  cùng tên miền.
- Ngày trên trang công khai hiển thị theo kiểu tiếng Anh dễ đọc (ví dụ "15 March 2026"), theo giờ Việt Nam.
- Nộp bài, phản biện, thanh toán phí, đăng ký tài khoản tác giả nằm ngoài phạm vi; nộp bài dùng biểu mẫu bên
  ngoài (ví dụ Google Form) qua link cấu hình.
- Website phục vụ một đơn vị tổ chức, mỗi lúc một hội thảo hiện hành; số tài khoản quản trị nhỏ (dưới 20 người).
- Lượng truy cập ở mức hội thảo khoa học (vài nghìn lượt mỗi ngày lúc cao điểm).
- Ngưỡng khóa đăng nhập (5 lần/15 phút), thời gian hết phiên (30 phút), hạn liên kết đặt lại mật khẩu (60 phút),
  giới hạn tệp (ảnh 5 MB, tài liệu 20 MB), lịch sao lưu (hằng ngày, giữ 30 bản) là mặc định hợp lý, có thể chỉnh ở bước plan.
- Có dịch vụ gửi email để gửi thư đặt lại mật khẩu.
- Bản đồ và video được nhúng từ dịch vụ bên ngoài (ví dụ Google Maps, YouTube) bằng link/mã nhúng do người dùng dán.
- Ban tổ chức cung cấp nội dung, ảnh, logo ban đầu; website mẫu VietESL 2025 chỉ dùng để tham chiếu cấu trúc,
  không sao chép nhận diện thương hiệu nếu không có quyền.
- Dữ liệu mẫu ban đầu được tạo sẵn các trang mặc định nêu ở FR-003 để ban tổ chức chỉ việc điền nội dung.
