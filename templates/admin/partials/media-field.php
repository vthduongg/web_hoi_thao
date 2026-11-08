<?php
/**
 * Ô chọn một tệp từ thư viện (media-picker.js mở hộp chọn, ghi id tệp vào ô ẩn).
 *
 * @var string $name                     tên trường gửi lên server (lưu id tệp)
 * @var string $label
 * @var ?\App\Models\MediaFile $media    tệp đang chọn
 * @var string $kind                     'image' (chỉ ảnh) hoặc '' (ảnh hoặc tài liệu)
 */
$kind ??= 'image';
?>
<div class="form-field media-field" data-media-field="<?= e($name) ?>" data-media-kind="<?= e($kind) ?>">
    <span class="form-field__label"><?= e($label) ?></span>
    <input type="hidden" name="<?= e($name) ?>" value="<?= e($media->id ?? '') ?>">
    <div class="media-field__preview" data-media-preview>
        <?php if ($media !== null && $media->isImage()): ?>
            <img src="<?= e(upload_url($media->previewPath())) ?>" alt="<?= e($media->altText ?? $media->originalName) ?>" width="200">
        <?php elseif ($media !== null): ?>
            <span><?= e($media->originalName) ?></span>
        <?php endif; ?>
    </div>
    <button type="button" class="button button--small" data-media-choose><?= e($kind === 'image' ? t('pages.choose_image') : t('media.choose_file')) ?></button>
    <button type="button" class="button button--small" data-media-clear><?= e(t('pages.remove_image')) ?></button>
</div>
