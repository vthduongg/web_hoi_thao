<?php
/**
 * Thư đặt lại mật khẩu (tiếng Việt).
 *
 * @var \App\Models\User $user
 * @var string $link
 * @var int $minutes
 */
?>
<p><?= e(t('auth.email_greeting', ['name' => $user->fullName])) ?></p>
<p><?= e(t('auth.email_body')) ?></p>
<p><a href="<?= e($link) ?>"><?= e($link) ?></a></p>
<p><?= e(t('auth.email_expiry', ['minutes' => $minutes])) ?></p>
<p><?= e(t('auth.email_ignore')) ?></p>
