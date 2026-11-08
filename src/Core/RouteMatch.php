<?php

declare(strict_types=1);

namespace App\Core;

/** Kết quả khớp route: handler dạng "Lớp@phương thức", tham số trên đường dẫn, quyền yêu cầu. */
final class RouteMatch
{
    /** @param array<string, string> $params */
    public function __construct(
        public readonly string $handler,
        public readonly array $params,
        public readonly ?string $permission,
    ) {
    }
}
