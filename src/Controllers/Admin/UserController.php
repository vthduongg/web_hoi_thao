<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Models\User;
use App\Repositories\UserRepository;

/** Quản lý tài khoản (chỉ quản trị viên — FR-019). */
final class UserController extends AdminController
{
    private function users(): UserRepository
    {
        return new UserRepository($this->services->db);
    }

    public function index(): Response
    {
        return $this->render('admin/users/index', ['pageTitle' => t('users.title'), 'users' => $this->users()->all()]);
    }

    public function create(): Response
    {
        return $this->form(null, ['email' => '', 'full_name' => '', 'role' => User::ROLE_EDITOR]);
    }

    public function store(): Response
    {
        $data = $this->formData();
        $errors = $this->validateUser($data, null, passwordRequired: true);
        if ($errors !== []) {
            return $this->form(null, $data, $errors);
        }

        $id = $this->users()->create($data['email'], $data['full_name'], $data['password'], $data['role']);
        $this->log('account_change', 'users', $id, $data['email']);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/users');
    }

    public function edit(string $id): Response
    {
        $user = $this->users()->find((int) $id) ?? $this->notFound();

        return $this->form($user, ['email' => $user->email, 'full_name' => $user->fullName, 'role' => $user->role]);
    }

    public function update(string $id): Response
    {
        $user = $this->users()->find((int) $id) ?? $this->notFound();
        $data = $this->formData();
        $errors = $this->validateUser($data, $user, passwordRequired: false);
        if ($errors !== []) {
            return $this->form($user, $data, $errors);
        }

        $this->users()->update($user->id, ['email' => $data['email'], 'full_name' => $data['full_name'], 'role' => $data['role']]);
        if ($data['password'] !== '') {
            $this->users()->setPassword($user->id, $data['password']);
            $this->users()->incrementEpoch($user->id);
        }
        $this->log('account_change', 'users', $user->id, $data['email']);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect('/admin/users');
    }

    public function lock(string $id): Response
    {
        $user = $this->users()->find((int) $id) ?? $this->notFound();
        if ($this->isLastActiveAdmin($user)) {
            $this->services->flash->error(t('users.last_admin'));

            return $this->redirect('/admin/users');
        }

        $this->users()->update($user->id, ['is_active' => 0]);
        $this->users()->incrementEpoch($user->id); // chấm dứt phiên đang mở của người bị khoá
        $this->log('account_change', 'users', $user->id, $user->email);
        $this->services->flash->success(t('users.locked'));

        return $this->redirect('/admin/users');
    }

    public function unlock(string $id): Response
    {
        $user = $this->users()->find((int) $id) ?? $this->notFound();
        $this->users()->update($user->id, ['is_active' => 1, 'failed_login_count' => 0, 'locked_until' => null]);
        $this->log('account_change', 'users', $user->id, $user->email);
        $this->services->flash->success(t('users.unlocked'));

        return $this->redirect('/admin/users');
    }

    /** @return array{email: string, full_name: string, role: string, password: string} */
    private function formData(): array
    {
        return [
            'email' => $this->request->input('email'),
            'full_name' => $this->request->input('full_name'),
            'role' => $this->request->input('role'),
            'password' => (string) ($this->request->post['password'] ?? ''),
        ];
    }

    private function validateUser(array $data, ?User $existing, bool $passwordRequired): array
    {
        $errors = $this->validate($data, [
            'email' => 'required|email|max:255',
            'full_name' => 'required|max:255',
            'role' => 'required|in:admin,editor',
            'password' => ($passwordRequired ? 'required|' : '') . 'min:8|max:200',
        ]);
        if (!isset($errors['email']) && $this->users()->emailTaken($data['email'], $existing?->id)) {
            $errors['email'] = t('users.email_taken');
        }
        if ($existing !== null && $data['role'] !== User::ROLE_ADMIN && $this->isLastActiveAdmin($existing)) {
            $errors['role'] = t('users.last_admin');
        }

        return $errors;
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return $user->isAdmin() && $user->isActive && $this->users()->countActiveAdmins() <= 1;
    }

    private function form(?User $user, array $values, array $errors = []): Response
    {
        return $this->render('admin/users/form', [
            'pageTitle' => $user === null ? t('users.create') : t('users.edit'),
            'user' => $user,
            'values' => $values,
            'errors' => $errors,
        ], $errors === [] ? 200 : 422);
    }
}
