<?php

declare(strict_types=1);

namespace App\Core;

/** Session lưu trong bộ nhớ — dùng cho test, không gửi cookie. */
final class ArraySessionStore implements SessionStore
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** Số lần session id đã được đổi (để test kiểm tra). */
    public int $regenerateCount = 0;

    public function &data(): array
    {
        return $this->data;
    }

    public function regenerate(): void
    {
        $this->regenerateCount++;
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}
