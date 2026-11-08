<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Lỗi HTTP có mã trạng thái (404, 403, 405, 419...). App bắt lỗi này và hiện trang lỗi phù hợp. */
final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : "HTTP $status", $status);
    }

    public static function notFound(): self
    {
        return new self(404);
    }

    public static function forbidden(): self
    {
        return new self(403);
    }
}
