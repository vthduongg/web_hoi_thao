<?php

declare(strict_types=1);

namespace App\Core;

/** Kết quả của một lần đăng nhập. */
enum LoginResult
{
    case Success;
    case InvalidCredentials;
    case Locked;
    case Inactive;
}
