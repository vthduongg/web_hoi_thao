<!--
SYNC IMPACT REPORT
- Version change: 1.0.0 → 2.0.0
- Lý do MAJOR: định nghĩa lại Nguyên tắc III theo cách không tương thích ngược (trang công khai
  không còn bắt buộc tiếng Việt).
- Modified principles:
  - III. Giao diện tiếng Việt → III. Ngôn ngữ giao diện theo đối tượng sử dụng
- Modified sections: Ràng buộc kỹ thuật (thông báo lỗi theo ngôn ngữ của từng giao diện);
  Quy trình phát triển & cổng chất lượng (điều kiện 3)
- Added sections: không có
- Removed sections: không có
- Follow-up TODOs: không có
-->

# Web Hội Thảo Constitution

## Core Principles

### I. Code dễ đọc là ưu tiên hàng đầu

- Khi phải chọn giữa code dễ đọc và code "thông minh" hoặc ngắn gọn hơn, PHẢI chọn code dễ đọc.
- Tên biến, hàm, file PHẢI mô tả rõ mục đích; không dùng viết tắt khó hiểu
  (trừ các quy ước phổ biến như `id`, `url`).
- Mỗi hàm PHẢI làm một việc rõ ràng; hàm dài trên ~40 dòng hoặc lồng quá 3 cấp
  PHẢI được tách nhỏ hoặc có lý do ghi rõ.
- Comment dùng để giải thích *tại sao*, không lặp lại *code làm gì*.
- Định dạng code PHẢI nhất quán trong toàn dự án (một bộ quy tắc format duy nhất).

**Lý do**: Code được đọc nhiều hơn được viết; người bảo trì sau (kể cả chính mình)
cần hiểu nhanh để sửa đúng.

### II. Mọi chức năng đều có test (KHÔNG THƯƠNG LƯỢNG)

- Mỗi chức năng người dùng thấy được và mỗi hàm xử lý nghiệp vụ PHẢI có ít nhất
  một test tự động kiểm tra luồng chính và một test cho trường hợp lỗi/biên quan trọng.
- Sửa lỗi PHẢI kèm test tái hiện lỗi đó trước khi sửa.
- Toàn bộ test PHẢI chạy được bằng một lệnh duy nhất và PHẢI pass trước khi
  một thay đổi được coi là hoàn thành.
- Test PHẢI dễ đọc như code chính: tên test mô tả hành vi mong đợi.

**Lý do**: Test là bằng chứng chức năng hoạt động và là lưới an toàn khi thay đổi.

### III. Ngôn ngữ giao diện theo đối tượng sử dụng

- **Trang công khai** PHẢI hiển thị bằng ngôn ngữ của hội thảo (được cấu hình trong
  thông tin chung của hội thảo; hiện tại là tiếng Anh). Mọi nhãn, nút, thông báo, thông báo
  lỗi, tiêu đề trang cố định của giao diện công khai PHẢI dùng ngôn ngữ đó, đúng chính tả.
- **Trang quản trị** PHẢI hiển thị toàn bộ bằng tiếng Việt có dấu, đúng chính tả: nhãn,
  nút, thông báo, thông báo lỗi, email gửi cho người quản trị, tiêu đề trang.
- Không trộn ngôn ngữ trong cùng một giao diện; nội dung do ban tổ chức nhập được hiển thị
  nguyên văn, không tự dịch.
- Văn bản cố định của mỗi giao diện PHẢI được gom ở một nơi dễ tìm (hằng số/file nội dung),
  không rải rác cứng trong logic.
- Ngày giờ: trang quản trị dùng định dạng Việt Nam (`dd/mm/yyyy`, giờ 24h); trang công khai
  dùng định dạng quen thuộc của ngôn ngữ hội thảo (ví dụ tiếng Anh: `15 March 2026`). Cả hai
  dùng múi giờ Asia/Ho_Chi_Minh.
- Mã nguồn (tên biến, hàm) dùng tiếng Anh; comment có thể dùng tiếng Việt.
- Mọi trang PHẢI dùng mã hóa UTF-8 và khai báo thuộc tính `lang` đúng với ngôn ngữ hiển thị
  (`lang="vi"` cho trang quản trị, ví dụ `lang="en"` cho trang công khai tiếng Anh).

**Lý do**: Khách của hội thảo quốc tế đọc ngôn ngữ của hội thảo, còn ban tổ chức là người Việt;
mỗi nhóm cần giao diện bằng ngôn ngữ mình dùng để thao tác nhanh và không nhầm.

### IV. Đơn giản trước hết

- Chỉ xây những gì đang thực sự cần (YAGNI); không thêm lớp trừu tượng,
  cấu hình hay tính năng "để sau này dùng".
- Ưu tiên giải pháp ít thành phần chuyển động nhất đáp ứng được yêu cầu.
- Mọi độ phức tạp thêm vào (pattern, tầng kiến trúc, dịch vụ mới) PHẢI được ghi
  lý do trong plan, kèm phương án đơn giản hơn đã bị loại và vì sao.

**Lý do**: Hệ thống đơn giản dễ hiểu, dễ test và ít lỗi hơn.

### V. Tối thiểu hóa thư viện phụ thuộc

- Chỉ thêm thư viện khi tự viết sẽ phức tạp, kém an toàn hoặc tốn công đáng kể
  (ví dụ: framework web, mã hóa mật khẩu, kết nối cơ sở dữ liệu, framework test).
- Không thêm thư viện cho việc mà ngôn ngữ/nền tảng chuẩn đã làm được trong vài dòng code.
- Mỗi thư viện mới PHẢI được ghi lý do trong plan: mục đích, vì sao không dùng
  được thư viện chuẩn, tình trạng bảo trì của thư viện.
- Phiên bản thư viện PHẢI được cố định (lock file).

**Lý do**: Mỗi phụ thuộc là chi phí bảo trì, rủi ro bảo mật và thêm điều cần học.

## Ràng buộc kỹ thuật

- Ứng dụng là website hội thảo; công nghệ cụ thể được chọn ở bước plan và PHẢI
  tuân thủ Nguyên tắc IV và V.
- Dữ liệu người dùng nhập vào PHẢI được kiểm tra hợp lệ phía server, kèm thông báo
  lỗi rõ ràng bằng ngôn ngữ của giao diện tương ứng (theo Nguyên tắc III).
- Không lưu bí mật (mật khẩu, khóa API) trong mã nguồn; dùng biến môi trường.
- Giao diện PHẢI dùng được trên cả điện thoại và máy tính.

## Quy trình phát triển & cổng chất lượng

- Mỗi tính năng đi theo trình tự Spec Kit: specify → plan → tasks → implement.
- Bước plan PHẢI có mục "Constitution Check" đối chiếu với 5 nguyên tắc trên.
- Một thay đổi chỉ được coi là xong khi:
  1. Toàn bộ test pass;
  2. Chức năng mới có test theo Nguyên tắc II;
  3. Văn bản hiển thị mới đúng ngôn ngữ của giao diện (công khai: ngôn ngữ hội thảo;
     quản trị: tiếng Việt) theo Nguyên tắc III;
  4. Không thêm thư viện chưa được ghi lý do theo Nguyên tắc V.
- Khi review, câu hỏi đầu tiên là: "Người khác đọc đoạn này có hiểu ngay không?"

## Governance

- Constitution này có hiệu lực cao hơn mọi quy ước làm việc khác của dự án.
- Sửa đổi constitution PHẢI được ghi lại (nội dung thay đổi, lý do) và cập nhật
  phiên bản theo semantic versioning:
  - MAJOR: bỏ hoặc định nghĩa lại một nguyên tắc theo cách không tương thích;
  - MINOR: thêm nguyên tắc/mục mới hoặc mở rộng đáng kể hướng dẫn;
  - PATCH: làm rõ câu chữ, sửa lỗi chính tả, không đổi ý nghĩa.
- Mỗi plan và mỗi lần review PHẢI kiểm tra tuân thủ; mọi ngoại lệ PHẢI được ghi lý do
  trong bảng theo dõi độ phức tạp của plan.

**Version**: 2.0.0 | **Ratified**: 2026-11-08 | **Last Amended**: 2026-11-08
