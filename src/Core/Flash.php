<?php

declare(strict_types=1);

namespace App\Core;

/** Thông báo hiện một lần ở trang kế tiếp (ví dụ "Đã lưu thành công"). */
final class Flash
{
    private const SESSION_KEY = '_flash';

    public function __construct(private readonly Session $session)
    {
    }

    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    /** @return list<array{type: string, message: string}> */
    public function take(): array
    {
        return $this->session->pull(self::SESSION_KEY, []);
    }

    private function add(string $type, string $message): void
    {
        $messages = $this->session->get(self::SESSION_KEY, []);
        $messages[] = ['type' => $type, 'message' => $message];
        $this->session->set(self::SESSION_KEY, $messages);
    }
}
