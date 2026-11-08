<?php
/**
 * Biểu mẫu dùng chung, vẽ từ danh sách trường do controller khai báo (CrudController::fields()).
 *
 * @var string $pageTitle
 * @var string $action
 * @var string $cancelUrl
 * @var list<array<string, mixed>> $fields
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var array<string, ?\App\Models\MediaFile> $mediaPreviews
 * @var string $csrfToken
 */
?>
<h1><?= e($pageTitle) ?></h1>
<div class="admin-card">
    <?= partial('admin/partials/form-errors', ['errors' => $errors]) ?>
    <div class="flash flash--error" data-alt-warning hidden role="alert" data-template="<?= e(t('pages.alt_warning', ['count' => '{count}'])) ?>"></div>
    <form method="post" action="<?= e($action) ?>">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <?php foreach ($fields as $field): ?>
            <?= partial('admin/crud/field', [
                'field' => $field,
                'value' => $values[$field['name']] ?? '',
                'errors' => $errors,
                'media' => $mediaPreviews[$field['name']] ?? null,
            ]) ?>
        <?php endforeach; ?>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
        <a class="button" href="<?= e($cancelUrl) ?>"><?= e(t('common.cancel')) ?></a>
    </form>
</div>
