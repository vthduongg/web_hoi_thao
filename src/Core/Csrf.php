<?php

declare(strict_types=1);

namespace App\Core;

/** Token chống giả mạo request (CSRF). Mọi biểu mẫu POST phải gửi kèm trường `_token`. */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function isValid(?string $submitted): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);

        return is_string($expected) && $expected !== '' && is_string($submitted) && hash_equals($expected, $submitted);
    }
}
