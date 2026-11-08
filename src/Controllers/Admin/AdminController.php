<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Response;
use App\Models\Conference;
use App\Models\User;

/**
 * Lớp cha của controller khu vực quản trị: render trong layout quản trị,
 * biết người đang đăng nhập và hội thảo đang sửa.
 */
abstract class AdminController extends Controller
{
    private const SESSION_EDITING_CONFERENCE = 'editing_conference_id';

    /** Người đang đăng nhập (route quản trị luôn yêu cầu đăng nhập nên không null). */
    protected function currentUser(): User
    {
        return $this->services->auth->user() ?? throw new \LogicException('Chưa đăng nhập.');
    }

    /** Hội thảo đang được sửa: chọn trong session, mặc định là hội thảo hiện hành. */
    protected function editingConference(): ?Conference
    {
        $id = $this->services->session->get(self::SESSION_EDITING_CONFERENCE);
        $conference = $id === null ? null : $this->services->conferences->find((int) $id);

        return $conference ?? $this->services->conferences->current();
    }

    protected function setEditingConference(int $conferenceId): void
    {
        $this->services->session->set(self::SESSION_EDITING_CONFERENCE, $conferenceId);
    }

    /** Render trang trong layout quản trị. */
    protected function render(string $template, array $vars = [], int $status = 200): Response
    {
        $user = $this->services->auth->user();
        $html = $this->services->view->page('admin/layout', $template, array_merge([
            'pageTitle' => '',
            'currentUser' => $user === null ? null : ['full_name' => $user->fullName, 'role' => $user->role],
            'csrfToken' => $this->services->csrf->token(),
            'flashMessages' => $this->services->flash->take(),
            'currentPath' => $this->request->path,
            'editingConference' => $user === null ? null : $this->editingConference(),
            'allConferences' => $user === null ? [] : $this->services->conferences->all(),
            'errors' => [],
        ], $vars));

        return Response::html($html, $status);
    }

    /** Render trang độc lập (không có thanh bên), dùng cho đăng nhập, quên mật khẩu. */
    protected function renderStandalone(string $template, array $vars = [], int $status = 200): Response
    {
        $html = $this->services->view->page('admin/standalone-layout', $template, array_merge([
            'csrfToken' => $this->services->csrf->token(),
            'flashMessages' => $this->services->flash->take(),
            'errors' => [],
        ], $vars));

        return Response::html($html, $status);
    }

    /** Kiểm tra trường "password" (tối thiểu 8 ký tự) và "password_confirmation" (phải khớp). */
    protected function validateNewPassword(): array
    {
        $errors = $this->validate($this->request->post, ['password' => 'required|min:8|max:200']);
        if ($errors === [] && ($this->request->post['password'] ?? '') !== ($this->request->post['password_confirmation'] ?? '')) {
            $errors['password_confirmation'] = t('auth.password_mismatch');
        }

        return $errors;
    }

    /** Ghi nhật ký hoạt động thay mặt người đang đăng nhập. */
    protected function log(string $action, string $entityType, ?int $entityId, ?string $label, ?int $conferenceId = null): void
    {
        $this->services->logger->log($this->services->auth->user()?->id, $action, $entityType, $entityId, $label, $conferenceId);
    }
}
