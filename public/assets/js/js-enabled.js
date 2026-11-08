// Đánh dấu "trình duyệt có JavaScript" TRƯỚC lần vẽ đầu tiên (file này nạp đồng bộ trong <head>, không defer).
// CSS dựa vào class này để thu gọn menu trên điện thoại; nếu đặt class muộn (sau khi trang đã hiện),
// menu đang mở sẽ co lại và làm nội dung nhảy lên (layout shift). Không viết inline vì CSP chặn script inline.
document.documentElement.classList.add('js');
