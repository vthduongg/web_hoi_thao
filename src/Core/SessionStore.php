<?php

declare(strict_types=1);

namespace App\Core;

/** Nơi lưu dữ liệu session. Chạy thật dùng NativeSessionStore; test dùng ArraySessionStore. */
interface SessionStore
{
    /** @return array<string, mixed> tham chiếu tới mảng dữ liệu session */
    public function &data(): array;

    /** Đổi session id (chống chiếm phiên khi đăng nhập). */
    public function regenerate(): void;

    /** Xoá toàn bộ session. */
    public function destroy(): void;
}
