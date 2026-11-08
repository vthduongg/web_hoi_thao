<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Repositories\UserRepository;

/** Người dùng tự đổi mật khẩu. */
final class ProfileController extends AdminController
{
    public function showPassword(): Response
    {
        return $this->render('admin/profile/password', ['pageTitle' => t('profile.title')]);
    }

    public function updatePassword(): Response
    {
        $user = $this->currentUser();
        $errors = [];
        if (!password_verify((string) ($this->request->post['current_password'] ?? ''), $user->passwordHash)) {
            $errors['current_password'] = t('profile.wrong_current');
        }
        $errors += $this->validateNewPassword();
        if ($errors !== []) {
            return $this->render('admin/profile/password', ['pageTitle' => t('profile.title'), 'errors' => $errors], 422);
        }

        $users = new UserRepository($this->services->db);
        $users->setPassword($user->id, (string) $this->request->post['password']);
        // Các phiên đăng nhập khác hết hiệu lực, phiên hiện tại được giữ
        $this->services->auth->refreshEpoch($users->incrementEpoch($user->id));
        $this->log('account_change', 'users', $user->id, $user->email);
        $this->services->flash->success(t('profile.changed'));

        return $this->redirect('/admin/profile/password');
    }
}
