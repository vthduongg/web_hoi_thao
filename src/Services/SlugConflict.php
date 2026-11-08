<?php

declare(strict_types=1);

namespace App\Services;

/** Không khôi phục được trang vì đường dẫn đã có trang khác dùng; cần chọn đường dẫn mới. */
final class SlugConflict extends \RuntimeException
{
}
