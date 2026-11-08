<?php
/**
 * @var bool   $sent  đã gửi yêu cầu (hiện cùng một thông báo dù email có tồn tại hay không)
 * @var string $csrfToken
 */
?>
<h1><?= e(t('auth.forgot_title')) ?></h1>
<?php if ($sent): ?>
    <div class="flash flash--success" role="status"><?= e(t('auth.forgot_sent')) ?></div>
    <p><a href="/admin/login"><?= e(t('auth.back_to_login')) ?></a></p>
<?php else: ?>
    <p><?= e(t('auth.forgot_help')) ?></p>
    <form method="post" action="/admin/forgot-password">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field">
            <label for="email"><?= e(t('auth.email')) ?></label>
            <input type="email" id="email" name="email" required autocomplete="username" autofocus>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('auth.forgot_button')) ?></button>
    </form>
    <p><a href="/admin/login"><?= e(t('auth.back_to_login')) ?></a></p>
<?php endif; ?>
