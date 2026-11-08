<?php
/**
 * Hộp chọn tệp (mở trong cửa sổ riêng). Bấm "Chọn" → media-picker.js gửi thông tin tệp về cửa sổ gọi.
 *
 * @var list<\App\Models\MediaFile> $items
 * @var string $query
 * @var string $kind  '' | 'image' | 'document'
 * @var string $csrfToken
 * @var list<array{type: string, message: string}> $flashMessages
 */
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('media.picker_title')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script src="<?= e(asset('js/media-picker.js')) ?>" defer></script>
</head>
<body class="picker-page">
<main id="main-content">
    <h1><?= e(t('media.picker_title')) ?></h1>

    <?php foreach ($flashMessages as $flash): ?>
        <div class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <form method="get" action="/admin/media/picker" class="filter-bar">
        <div class="form-field">
            <label for="q"><?= e(t('media.search_label')) ?></label>
            <input type="search" id="q" name="q" value="<?= e($query) ?>">
        </div>
        <div class="form-field">
            <label for="kind"><?= e(t('media.kind_label')) ?></label>
            <select id="kind" name="kind">
                <?php foreach (['' => 'media.kind_all', 'image' => 'media.kind_image', 'document' => 'media.kind_document'] as $value => $labelKey): ?>
                    <option value="<?= e($value) ?>" <?= $kind === $value ? 'selected' : '' ?>><?= e(t($labelKey)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="button"><?= e(t('media.search')) ?></button>
    </form>

    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="admin-card">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="kind" value="<?= e($kind) ?>">
        <div class="form-field">
            <label for="files"><?= e(t('media.upload_label')) ?></label>
            <input type="file" id="files" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx">
            <p class="form-field__hint"><?= e(t('media.upload_hint')) ?></p>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('media.upload')) ?></button>
    </form>

    <?php if ($items === []): ?>
        <p><?= e(t('media.empty')) ?></p>
    <?php else: ?>
        <ul class="media-grid">
            <?php foreach ($items as $item): ?>
                <li class="media-grid__item" data-media-item
                    data-media-id="<?= e($item->id) ?>"
                    data-media-url="<?= e(upload_url($item->storedPath)) ?>"
                    data-media-preview-url="<?= e(upload_url($item->previewPath())) ?>"
                    data-media-name="<?= e($item->originalName) ?>"
                    data-media-alt="<?= e($item->altText ?? '') ?>"
                    data-media-kind="<?= e($item->kind) ?>">
                    <?php if ($item->isImage()): ?>
                        <img src="<?= e(upload_url($item->previewPath())) ?>" alt="" loading="lazy" width="160" height="120">
                    <?php else: ?>
                        <span class="media-grid__icon" aria-hidden="true">📄</span>
                    <?php endif; ?>
                    <span class="media-grid__name"><?= e($item->originalName) ?></span>
                    <button type="button" class="button button--small button--primary" data-media-select><?= e(t('media.choose')) ?></button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>
</body>
</html>
