<?php
/**
 * @var array<string, string> $errors
 * @var string $csrfToken
 */
?>
<h1><?= e(t('profile.title')) ?></h1>
<div class="admin-card">
    <?= partial('admin/partials/form-errors', ['errors' => $errors]) ?>
    <form method="post" action="/admin/profile/password">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field<?= isset($errors['current_password']) ? ' form-field--invalid' : '' ?>">
            <label for="current_password"><?= e(t('profile.current_password')) ?></label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'current_password']) ?>
        </div>
        <div class="form-field<?= isset($errors['password']) ? ' form-field--invalid' : '' ?>">
            <label for="password"><?= e(t('auth.new_password')) ?></label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
            <p class="form-field__hint"><?= e(t('auth.password_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'password']) ?>
        </div>
        <div class="form-field<?= isset($errors['password_confirmation']) ? ' form-field--invalid' : '' ?>">
            <label for="password_confirmation"><?= e(t('auth.confirm_password')) ?></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'password_confirmation']) ?>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
    </form>
</div>
