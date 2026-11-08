<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bảng route đơn giản: [phương thức, mẫu đường dẫn, handler, quyền].
 *
 * Mẫu đường dẫn dùng tham số trong ngoặc nhọn. Một số tên tham số có quy tắc riêng:
 *   {year} = 4 chữ số, {slug} = chữ thường a-z, số, dấu gạch ngang, {id} và tên kết thúc bằng Id = số.
 * Route được khớp theo thứ tự khai báo — route cụ thể phải khai báo trước route chung.
 */
final class Router
{
    private const PARAM_PATTERNS = [
        'year' => '\d{4}',
        'slug' => '[a-z0-9-]+',
        'token' => '[A-Za-z0-9]+',
        'type' => '[a-z_]+',
    ];

    /** @var list<array{method: string, regex: string, handler: string, permission: ?string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler, ?string $permission = null): void
    {
        $this->add('GET', $pattern, $handler, $permission);
    }

    public function post(string $pattern, string $handler, ?string $permission = null): void
    {
        $this->add('POST', $pattern, $handler, $permission);
    }

    public function add(string $method, string $pattern, string $handler, ?string $permission = null): void
    {
        $this->routes[] = [
            'method' => $method,
            'regex' => self::toRegex($pattern),
            'handler' => $handler,
            'permission' => $permission,
        ];
    }

    /** @throws HttpException 404 khi không có route, 405 khi có route nhưng sai phương thức */
    public function match(string $method, string $path): RouteMatch
    {
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $pathMatchedOtherMethod = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $pathMatchedOtherMethod = true;
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return new RouteMatch($route['handler'], $params, $route['permission']);
        }

        throw new HttpException($pathMatchedOtherMethod ? 405 : 404);
    }

    private static function toRegex(string $pattern): string
    {
        $regex = preg_replace_callback('/\{(\w+)\}/', static function (array $m): string {
            $name = $m[1];
            $rule = self::PARAM_PATTERNS[$name]
                ?? ($name === 'id' || str_ends_with($name, 'Id') ? '\d+' : '[^/]+');

            return "(?<$name>$rule)";
        }, $pattern);

        return '#^' . $regex . '$#';
    }
}
