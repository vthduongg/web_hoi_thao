<?php
/**
 * Dải "Bản xem trước" — NGOẠI LỆ NGÔN NGỮ DUY NHẤT trên giao diện công khai (contracts/ui-theme.md):
 * chữ tiếng Việt vì chỉ người quản trị đang dùng chức năng Xem trước mới nhìn thấy.
 *
 * @var string $text  chuỗi tiếng Việt (lấy từ lang/vi.php trước khi chuyển sang ngôn ngữ công khai)
 */
?>
<div class="notice-banner notice-banner--preview" role="status" lang="vi">
    <div class="container"><?= e($text) ?></div>
</div>
