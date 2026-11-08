<?php
/**
 * Tạo / sửa tài khoản.
 *
 * @var ?\App\Models\User $user   null khi tạo mới
 * @var array{email: string, full_name: string, role: string} $values
 * @var array<string, string> $errors
 * @var string $csrfToken
 */
$action = $user === null ? '/admin/users/new' : '/admin/users/' . $user->id;
?>
<h1><?= e($user === null ? t('users.create') : t('users.edit')) ?></h1>
<div class="admin-card">
    <?= partial('admin/partials/form-errors', ['errors' => $errors]) ?>
    <form method="post" action="<?= e($action) ?>">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field<?= isset($errors['full_name']) ? ' form-field--invalid' : '' ?>">
            <label for="full_name"><?= e(t('users.full_name')) ?></label>
            <input type="text" id="full_name" name="full_name" value="<?= e($values['full_name']) ?>" required maxlength="255">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'full_name']) ?>
        </div>
        <div class="form-field<?= isset($errors['email']) ? ' form-field--invalid' : '' ?>">
            <label for="email"><?= e(t('users.email')) ?></label>
            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required maxlength="255">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'email']) ?>
        </div>
        <div class="form-field<?= isset($errors['role']) ? ' form-field--invalid' : '' ?>">
            <label for="role"><?= e(t('users.role')) ?></label>
            <select id="role" name="role">
                <?php foreach (['editor', 'admin'] as $role): ?>
                    <option value="<?= e($role) ?>" <?= $values['role'] === $role ? 'selected' : '' ?>><?= e(t('users.role_' . $role)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="form-field__hint"><?= e(t('users.role_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'role']) ?>
        </div>
        <div class="form-field<?= isset($errors['password']) ? ' form-field--invalid' : '' ?>">
            <label for="password"><?= e($user === null ? t('users.initial_password') : t('users.new_password_optional')) ?></label>
            <input type="password" id="password" name="password" <?= $user === null ? 'required' : '' ?> minlength="8" autocomplete="new-password">
            <p class="form-field__hint"><?= e(t('auth.password_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'password']) ?>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
        <a class="button" href="/admin/users"><?= e(t('common.cancel')) ?></a>
    </form>
</div>
