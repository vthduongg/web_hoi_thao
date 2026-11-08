<?php
/**
 * Ô chọn một ảnh từ thư viện (media-picker.js mở hộp chọn, ghi id ảnh vào ô ẩn).
 *
 * @var string $name                     tên trường gửi lên server (lưu id ảnh)
 * @var string $label
 * @var ?\App\Models\MediaFile $media    ảnh đang chọn
 */
?>
<div class="form-field media-field" data-media-field="<?= e($name) ?>">
    <span class="form-field__label"><?= e($label) ?></span>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($media->id ?? '') ?>">
    <div class="media-field__preview" data-media-preview>
        <?php if ($media !== null && $media->isImage()): ?>
            <img src="<?= e(upload_url($media->previewPath())) ?>" alt="<?= e($media->altText ?? $media->originalName) ?>" width="200">
        <?php endif; ?>
    </div>
    <button type="button" class="button button--small" data-media-choose><?= e(t('pages.choose_image')) ?></button>
    <button type="button" class="button button--small" data-media-clear><?= e(t('pages.remove_image')) ?></button>
</div>
