<?php
/**
 * Sửa mô tả của một tệp.
 *
 * @var \App\Models\MediaFile $media
 * @var array<string, string> $errors
 * @var string $csrfToken
 */
?>
<h1><?= e(t('media.edit')) ?>: <?= e($media->originalName) ?></h1>
<div class="admin-card">
    <?php if ($media->isImage()): ?>
        <p><img src="<?= e(upload_url($media->thumbLargePath ?? $media->storedPath)) ?>" alt="<?= e($media->altText ?? '') ?>" width="480"></p>
    <?php endif; ?>
    <p class="form-field__hint"><?= e(t('media.path')) ?>: <?= e(upload_url($media->storedPath)) ?></p>
    <?= partial('admin/partials/form-errors', ['errors' => $errors]) ?>
    <form method="post" action="/admin/media/<?= e($media->id) ?>">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field<?= isset($errors['alt_text']) ? ' form-field--invalid' : '' ?>">
            <label for="alt_text"><?= e(t('media.alt_text')) ?></label>
            <input type="text" id="alt_text" name="alt_text" value="<?= e($media->altText ?? '') ?>" maxlength="500">
            <p class="form-field__hint"><?= e(t('media.alt_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'alt_text']) ?>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
        <a class="button" href="/admin/media"><?= e(t('common.cancel')) ?></a>
    </form>
</div>
