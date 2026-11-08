<?php

declare(strict_types=1);

namespace App\Services;

/** Không xoá vĩnh viễn được (ví dụ tệp vẫn đang được slider hoặc logo nhà tài trợ dùng). */
final class PurgeRefused extends \RuntimeException
{
}
