<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\ConferenceRepository;
use App\Services\ActivityLogger;
use App\Services\HtmlSanitizer;
use App\Services\Mailer;

/**
 * Nơi giữ các đối tượng dùng chung trong một request (CSDL, session, view...).
 *
 * Không có "phép màu": mọi thứ được tạo tường minh trong AppFactory.
 * Controller nhận đối tượng này và lấy thứ mình cần.
 */
final class Services
{
    /** Đăng nhập và phân quyền (gán trong AppFactory). */
    public ?Auth $auth = null;

    /** Gửi email (gán trong AppFactory; khi chạy test chỉ ghi vào bộ nhớ). */
    public ?Mailer $mailer = null;

    public function __construct(
        public readonly string $rootDir,
        public readonly Database $db,
        public readonly Session $session,
        public readonly Csrf $csrf,
        public readonly Flash $flash,
        public readonly View $view,
        public readonly HtmlSanitizer $sanitizer,
        public readonly ActivityLogger $logger,
        public readonly ConferenceRepository $conferences,
        public readonly string $environment,
    ) {
    }

    public function lang(string $locale): Lang
    {
        return Lang::load($locale, $this->rootDir . '/lang');
    }

    public function isTesting(): bool
    {
        return $this->environment === 'testing';
    }
}
