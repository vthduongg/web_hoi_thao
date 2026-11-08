<?php
/**
 * Nút nộp bài: mở biểu mẫu bên ngoài do quản trị viên cấu hình (FR-008).
 *
 * @var \App\Models\Conference $conference
 */
?>
<?php if (($conference->submissionUrl ?? '') !== ''): ?>
    <a class="button button--primary" href="<?= e($conference->submissionUrl) ?>" target="_blank" rel="noopener"><?= e(t('submit.button')) ?></a>
<?php else: ?>
    <span class="button" role="link" aria-disabled="true"><?= e(t('submit.soon')) ?></span>
<?php endif; ?>
