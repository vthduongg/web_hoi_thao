# Hướng dẫn vận hành

Tài liệu cho người quản lý máy chủ: việc chạy hằng ngày, sao lưu, khôi phục, bảo mật.

## 1. Việc hằng ngày: dọn thùng rác + sao lưu

Script `bin/daily.php` làm hai việc:

1. Xoá vĩnh viễn các mục đã nằm trong **thùng rác quá 30 ngày** (tệp đang được slider/logo nhà tài trợ dùng thì giữ lại).
2. **Sao lưu**: CSDL ra tệp `conference-YYYYMMDD.bak` và thư mục `public/uploads` ra tệp `uploads-YYYYMMDD.zip`;
   chỉ giữ **30 bản mới nhất** của mỗi loại.

Chạy thử bằng tay:

```powershell
cd D:\duong-dan\web_hoi_thao
php bin/daily.php
```

Thành công: in "Đã xoá vĩnh viễn …" và "Đã sao lưu: …", mã thoát 0. Lỗi sao lưu: in "LỖI SAO LƯU: …" và mã thoát 1
(việc dọn thùng rác vẫn được làm). Mọi lần chạy đều ghi vào **Nhật ký** (hành động "Sao lưu", "Xoá vĩnh viễn").

### Đặt lịch chạy lúc 02:00 bằng Windows Task Scheduler

1. Mở **Task Scheduler** → **Create Task…**
2. Tab **General**: đặt tên `Conference daily`; chọn "Run whether user is logged on or not".
3. Tab **Triggers** → **New…** → Daily, lúc **02:00**.
4. Tab **Actions** → **New…**:
   - Program/script: đường dẫn `php.exe` (ví dụ `C:\php\php.exe`)
   - Add arguments: `bin\daily.php`
   - Start in: thư mục dự án (ví dụ `D:\duong-dan\web_hoi_thao`)
5. Bấm OK, nhập mật khẩu tài khoản chạy task. Kiểm tra cột **Last Run Result** = `0x0` sau lần chạy đầu.

(Linux: thêm vào crontab `0 2 * * * cd /var/www/web_hoi_thao && php bin/daily.php`.)

## 2. Cấu hình sao lưu

### Quyền trên SQL Server

Tài khoản CSDL mà website dùng (biến `DB_USER`, hoặc tài khoản Windows chạy website khi để trống) cần vai trò
**`db_backupoperator`** trên CSDL `conference`:

```sql
USE conference;
ALTER ROLE db_backupoperator ADD MEMBER [ten_tai_khoan];
```

### Thư mục sao lưu (`BACKUP_DIR` trong `.env`)

Lệnh `BACKUP DATABASE` do **SQL Server** thực hiện, nên đường dẫn là đường dẫn **nhìn từ máy SQL Server**;
còn tệp `.zip` do **máy chạy website** tạo. Vì vậy:

| Trường hợp | Cấu hình `BACKUP_DIR` |
|---|---|
| SQL Server và website cùng một máy | Đường dẫn cục bộ, ví dụ `C:\backup\conference`. Cấp quyền ghi thư mục cho tài khoản dịch vụ SQL Server (ví dụ `NT Service\MSSQL$SQLEXPRESS`) và tài khoản chạy website. |
| SQL Server ở máy khác | Thư mục chia sẻ mạng (UNC), ví dụ `\\fileserver\backup\conference`. Tài khoản dịch vụ SQL Server phải **ghi** được, máy website phải **ghi và xoá** được (để tạo `.zip` và dọn bản cũ). |

Lỗi thường gặp:
- "Thư mục sao lưu không tồn tại hoặc không ghi được" → kiểm tra `BACKUP_DIR` và quyền thư mục của tài khoản chạy website.
- "Không sao lưu được CSDL … Operating system error 5 (Access is denied)" → tài khoản dịch vụ SQL Server chưa có quyền ghi thư mục.
- "BACKUP DATABASE permission denied" → tài khoản CSDL chưa có vai trò `db_backupoperator`.

Nên chép thư mục sao lưu sang ổ đĩa / máy khác định kỳ (sao lưu nằm cùng ổ với máy chủ sẽ mất cùng máy chủ).

## 3. Khôi phục

### Khôi phục CSDL từ tệp `.bak`

Dùng SQL Server Management Studio (chuột phải **Databases → Restore Database…**) hoặc lệnh:

```sql
-- Khôi phục sang CSDL tạm để kiểm tra trước
RESTORE DATABASE conference_restore_check
FROM DISK = N'C:\backup\conference\conference-20270318.bak'
WITH MOVE 'conference' TO 'C:\SQLData\conference_restore_check.mdf',
     MOVE 'conference_log' TO 'C:\SQLData\conference_restore_check_log.ldf';
```

(Tên logic `conference` / `conference_log` xem bằng `RESTORE FILELISTONLY FROM DISK = N'…'`.)
Khi đã kiểm tra đúng dữ liệu, khôi phục đè lên CSDL thật: tạm dừng website (IIS) → `RESTORE DATABASE conference … WITH REPLACE` → bật lại website.

### Khôi phục tệp tải lên từ `.zip`

Giải nén `uploads-YYYYMMDD.zip` vào thư mục `public/` của website (tệp nằm trong thư mục con `uploads/` trong tệp nén),
chọn ghi đè khi được hỏi.

## 4. Bảo mật

- Tệp `.env` chứa mật khẩu: chỉ tài khoản chạy website đọc được; không đưa lên git.
- Trên máy chủ thật đặt `APP_URL` bắt đầu bằng `https://` → cookie đăng nhập tự bật cờ `Secure`.
- `public/web.config` chặn chạy script trong `public/uploads/` và gửi header `X-Content-Type-Options: nosniff` cho tệp tải lên.
- Website gửi header Content-Security-Policy riêng cho trang công khai và trang quản trị (research.md R18).
- Thư viện tệp không nhận SVG và kiểm tra loại tệp theo nội dung, không theo đuôi tệp.
