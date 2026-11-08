<?php
/**
 * Nút xoá có hộp xác nhận (confirm-delete.js). Xoá = chuyển vào thùng rác.
 *
 * @var string $action     đường dẫn POST xoá
 * @var string $csrfToken
 * @var string $label      chữ trên nút (mặc định "Xoá")
 * @var string $message    câu hỏi xác nhận
 */
?>
<form method="post" action="<?= e($action) ?>" class="inline-form" data-confirm="<?= e($message ?? t('common.confirm_delete')) ?>">
    <input type="hidden" name="_token" value="<?= e($csrfToken ?? '') ?>">
    <button type="submit" class="button button--small button--danger"><?= e($label ?? t('common.delete')) ?></button>
</form>
