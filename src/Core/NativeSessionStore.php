<?php

declare(strict_types=1);

namespace App\Core;

/** Session thật của PHP với cookie an toàn: HttpOnly, SameSite=Lax, Secure khi chạy https. */
final class NativeSessionStore implements SessionStore
{
    public function __construct(bool $secureCookie)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('conf_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public function &data(): array
    {
        return $_SESSION;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
