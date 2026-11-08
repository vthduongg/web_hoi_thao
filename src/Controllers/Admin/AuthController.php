<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Env;
use App\Core\LoginResult;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\InvalidResetToken;
use App\Services\PasswordResetService;

/** Đăng nhập, đăng xuất, quên mật khẩu, đặt lại mật khẩu. */
final class AuthController extends AdminController
{
    public function showLogin(): Response
    {
        if ($this->services->auth->check()) {
            return $this->redirect('/admin');
        }

        return $this->renderStandalone('admin/auth/login', ['email' => '', 'error' => null, 'next' => $this->request->input('next')]);
    }

    public function login(): Response
    {
        $email = $this->request->input('email');
        $result = $this->services->auth->attempt($email, (string) ($this->request->post['password'] ?? ''));

        if ($result === LoginResult::Success) {
            return $this->redirect(self::safeNext($this->request->input('next')));
        }

        $error = match ($result) {
            LoginResult::Locked => t('auth.locked'),
            LoginResult::Inactive => t('auth.inactive'),
            default => t('auth.invalid'),
        };

        return $this->renderStandalone('admin/auth/login', ['email' => $email, 'error' => $error, 'next' => $this->request->input('next')], 422);
    }

    public function logout(): Response
    {
        $this->services->auth->logout();

        return $this->redirect('/admin/login');
    }

    public function showForgot(): Response
    {
        return $this->renderStandalone('admin/auth/forgot-password', ['sent' => false]);
    }

    public function sendResetLink(): Response
    {
        $this->resetService()->request($this->request->input('email'));

        // Cùng một thông báo dù email có tồn tại hay không (không để lộ danh sách tài khoản)
        return $this->renderStandalone('admin/auth/forgot-password', ['sent' => true]);
    }

    public function showReset(string $token): Response
    {
        if (!$this->resetService()->isValid($token)) {
            return $this->renderStandalone('admin/auth/reset-invalid', [], 410);
        }

        return $this->renderStandalone('admin/auth/reset-password', ['token' => $token]);
    }

    public function reset(string $token): Response
    {
        $errors = $this->validateNewPassword();
        if ($errors !== []) {
            return $this->renderStandalone('admin/auth/reset-password', ['token' => $token, 'errors' => $errors], 422);
        }

        try {
            $this->resetService()->reset($token, (string) $this->request->post['password']);
        } catch (InvalidResetToken) {
            return $this->renderStandalone('admin/auth/reset-invalid', [], 410);
        }
        $this->services->flash->success(t('auth.reset_done'));

        return $this->redirect('/admin/login');
    }

    /** Chỉ cho chuyển hướng về đường dẫn trong khu vực quản trị (chống chuyển hướng ra trang lạ). */
    private static function safeNext(string $next): string
    {
        return preg_match('#^/admin(/[A-Za-z0-9/_-]*)?$#', $next) ? $next : '/admin';
    }

    private function resetService(): PasswordResetService
    {
        return new PasswordResetService(
            $this->services->db,
            new UserRepository($this->services->db),
            $this->services->mailer,
            Env::get('APP_URL', 'http://localhost:8000'),
        );
    }
}
