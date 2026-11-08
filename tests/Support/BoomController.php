<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Controllers\Controller;
use App\Core\Response;

/** Controller chỉ dùng trong test: luôn ném lỗi để kiểm tra trang 500. */
final class BoomController extends Controller
{
    public function index(): Response
    {
        throw new \RuntimeException('Class Foo not found. Stack trace: secret');
    }
}
