<?php

declare(strict_types=1);

namespace App\Core;

/** Đọc/ghi dữ liệu session. */
final class Session
{
    public function __construct(private readonly SessionStore $store)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store->data()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $data = &$this->store->data();
        $data[$key] = $value;
    }

    public function forget(string $key): void
    {
        $data = &$this->store->data();
        unset($data[$key]);
    }

    /** Lấy giá trị rồi xoá luôn (dùng cho thông báo flash). */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function regenerate(): void
    {
        $this->store->regenerate();
    }

    public function destroy(): void
    {
        $this->store->destroy();
    }
}
