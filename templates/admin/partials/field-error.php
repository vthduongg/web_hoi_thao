<?php
/**
 * Lỗi của một trường biểu mẫu.
 *
 * @var array<string, string> $errors
 * @var string $field
 */
?>
<?php if (isset($errors[$field])): ?>
    <p class="form-field__error" id="<?= e($field) ?>-error"><?= e($errors[$field]) ?></p>
<?php endif; ?>
