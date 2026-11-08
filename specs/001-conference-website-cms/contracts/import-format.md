# Contract: Tệp nhập thành viên ban (FR-031)

- Định dạng: `.xlsx` (đọc sheet đầu tiên) hoặc `.csv` (UTF-8, có hoặc không BOM; dấu phân cách `,` hoặc `;` tự nhận).
- Dung lượng tối đa 2 MB, tối đa 500 dòng dữ liệu.
- Dòng 1 là tiêu đề cột. Tên cột không phân biệt hoa/thường, chấp nhận tiếng Việt hoặc tiếng Anh:

| Cột (vi) | Cột (en) | Bắt buộc | Giới hạn |
|---|---|---|---|
| Học hàm học vị | Academic title | không | 100 ký tự |
| Họ tên | Full name | **có** | 255 ký tự |
| Chức vụ đơn vị | Position and affiliation | không | 500 ký tự |
| Vai trò | Role | không | 100 ký tự |

- Dòng trống hoàn toàn bị bỏ qua. Thứ tự thành viên = thứ tự dòng, nối tiếp sau các thành viên đã có.
- **Tất cả hoặc không**: có bất kỳ lỗi nào → không thêm dòng nào, hiển thị bảng lỗi
  `Dòng {n}: {mô tả lỗi}` (ví dụ "Dòng 7: thiếu Họ tên").
- Thiếu cột bắt buộc → lỗi "Tệp thiếu cột: Họ tên" + link tải tệp mẫu.
- Tệp mẫu tải về (tệp tĩnh): `/assets/templates/committee-import-template.xlsx` và
  `/assets/templates/committee-import-template.csv` (nằm trong `public/assets/templates/`).
