<?php

declare(strict_types=1);

namespace App\Services;

/** Token đặt lại mật khẩu không tồn tại, đã dùng, hoặc đã hết hạn. */
final class InvalidResetToken extends \RuntimeException
{
}
