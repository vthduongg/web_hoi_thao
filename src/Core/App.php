<?php

declare(strict_types=1);

namespace App\Core;

use App\Controllers\Controller;

/**
 * Xử lý một request: khớp route → kiểm CSRF → kiểm quyền → gọi controller → gắn header bảo mật.
 *
 * Lỗi được chuyển thành trang lỗi đúng ngôn ngữ: dưới /admin dùng tiếng Việt,
 * còn lại dùng ngôn ngữ công khai của hội thảo (constitution, Nguyên tắc III).
 */
final class App
{
    /** Chính sách bảo mật nội dung (research.md R18). */
    private const CSP_PUBLIC = "default-src 'self'; img-src 'self' data:; "
        . "frame-src https://www.youtube-nocookie.com https://www.youtube.com https://www.google.com; "
        . "script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'";

    private const CSP_ADMIN = "default-src 'self'; img-src 'self' data: blob:; "
        . "frame-src https://www.youtube-nocookie.com https://www.youtube.com https://www.google.com; "
        . "script-src 'self'; style-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self'; form-action 'self'";

    public function __construct(private readonly Router $router, private readonly Services $services)
    {
    }

    public function services(): Services
    {
        return $this->services;
    }

    public function handle(Request $request): Response
    {
        try {
            $this->services->logger->setIpAddress($request->ip);
            $this->chooseLanguage($request);
            $match = $this->router->match($request->method, $request->path);

            if ($request->method === 'POST' && !$this->services->csrf->isValid($request->post['_token'] ?? null)) {
                throw new HttpException(419);
            }

            $denied = $this->authorize($match, $request);
            $response = $denied ?? $this->callController($match, $request);
        } catch (HttpException $e) {
            $response = $this->errorResponse($e->status, $request);
        } catch (\Throwable $e) {
            error_log((string) $e);
            if ($this->services->isTesting() && getenv('APP_DEBUG_ERRORS') === '1') {
                throw $e;
            }
            $response = $this->errorResponse(500, $request);
        }

        return $this->withSecurityHeaders($response, $request);
    }

    private function chooseLanguage(Request $request): void
    {
        if ($request->isAdminArea()) {
            Lang::setCurrent($this->services->lang('vi'));

            return;
        }

        try {
            $locale = $this->services->conferences->current()?->publicLanguage ?? 'en';
        } catch (\Throwable) {
            $locale = 'en'; // CSDL lỗi vẫn phải hiện được trang lỗi
        }
        Lang::setCurrent($this->services->lang($locale));
    }

    /** Trả về phản hồi từ chối (chuyển tới đăng nhập / 403), hoặc null nếu được phép. */
    private function authorize(RouteMatch $match, Request $request): ?Response
    {
        if ($match->permission === null) {
            return null;
        }

        $auth = $this->services->auth ?? throw new \LogicException('Chưa cấu hình Auth.');
        if (!$auth->check()) {
            return Response::redirect('/admin/login?next=' . rawurlencode($request->path));
        }
        if (!$auth->can($match->permission)) {
            throw HttpException::forbidden();
        }

        return null;
    }

    private function callController(RouteMatch $match, Request $request): Response
    {
        [$class, $method] = explode('@', $match->handler);
        // "Public\HomeController" → App\Controllers\Public\HomeController; tên bắt đầu bằng "\" là tên đầy đủ.
        $class = str_starts_with($class, '\\') ? $class : 'App\\Controllers\\' . $class;

        /** @var Controller $controller */
        $controller = new $class($this->services, $request);

        return $controller->$method(...$match->params);
    }

    private function errorResponse(int $status, Request $request): Response
    {
        try {
            if ($request->isAdminArea()) {
                Lang::setCurrent($this->services->lang('vi'));
                $html = $this->services->view->render('admin/error', ['status' => $status]);
            } else {
                $html = $this->services->view->render($status === 404 ? 'public/404' : 'public/500', ['status' => $status]);
            }
        } catch (\Throwable $e) {
            error_log((string) $e);
            $html = '<!doctype html><title>Error</title><h1>Error ' . $status . '</h1>';
        }

        return Response::html($html, $status);
    }

    private function withSecurityHeaders(Response $response, Request $request): Response
    {
        return $response
            ->withHeader('Content-Security-Policy', $request->isAdminArea() ? self::CSP_ADMIN : self::CSP_PUBLIC)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
