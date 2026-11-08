<?php
/**
 * @var string  $email
 * @var ?string $error
 * @var string  $next
 * @var string  $csrfToken
 */
?>
<h1><?= e(t('auth.login_title')) ?></h1>
<?php if ($error !== null): ?>
    <div class="flash flash--error" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" action="/admin/login<?= $next !== '' ? '?next=' . e(rawurlencode($next)) : '' ?>">
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <div class="form-field">
        <label for="email"><?= e(t('auth.email')) ?></label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="username" autofocus>
    </div>
    <div class="form-field">
        <label for="password"><?= e(t('auth.password')) ?></label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <button type="submit" class="button button--primary"><?= e(t('auth.login_button')) ?></button>
</form>
<p><a href="/admin/forgot-password"><?= e(t('auth.forgot_link')) ?></a></p>
