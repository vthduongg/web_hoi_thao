<?php

declare(strict_types=1);

namespace App\Services;

/** Sao lưu không thành công; thông báo (tiếng Việt) nêu nguyên nhân và cách xử lý. */
final class BackupFailed extends \RuntimeException
{
}
