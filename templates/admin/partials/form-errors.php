<?php
/**
 * Thông báo tổng khi biểu mẫu có lỗi. Lỗi từng trường hiện cạnh trường (partial field-error).
 *
 * @var array<string, string> $errors
 */
?>
<?php if (($errors ?? []) !== []): ?>
    <div class="flash flash--error" role="alert"><?= e(t('validation.form_has_errors')) ?></div>
<?php endif; ?>
