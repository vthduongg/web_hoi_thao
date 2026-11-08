<?php

declare(strict_types=1);

namespace App\Services;

/** Trang đã bị người khác sửa trong lúc mình đang sửa (số phiên bản không khớp). */
final class EditConflict extends \RuntimeException
{
}
