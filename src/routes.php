<?php

declare(strict_types=1);

// Bảng route của toàn website.
// Quyền: null = công khai, 'E' = quản trị viên + biên tập viên, 'A' = chỉ quản trị viên.
// Route cụ thể phải khai báo TRƯỚC route chung (ví dụ /past-conferences trước /{slug}).

use App\Core\Router;

return static function (Router $r): void {
};
