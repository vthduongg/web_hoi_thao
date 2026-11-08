<?php
/**
 * Thư viện tệp: tải lên, tìm, lọc, sửa mô tả, xoá.
 *
 * @var list<\App\Models\MediaFile> $items
 * @var string $query
 * @var string $kind
 * @var string $csrfToken
 */
?>
<h1><?= e(t('media.title')) ?></h1>

<form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="admin-card">
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="return" value="library">
    <div class="form-field">
        <label for="files"><?= e(t('media.upload_label')) ?></label>
        <input type="file" id="files" name="files[]" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx">
        <p class="form-field__hint"><?= e(t('media.upload_hint')) ?></p>
    </div>
    <button type="submit" class="button button--primary"><?= e(t('media.upload')) ?></button>
</form>

<form method="get" action="/admin/media" class="admin-card filter-bar">
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

<?php if ($items === []): ?>
    <p><?= e(t('media.empty')) ?></p>
<?php else: ?>
    <ul class="media-grid">
        <?php foreach ($items as $item): ?>
            <li class="media-grid__item">
                <?php if ($item->isImage()): ?>
                    <img src="<?= e(upload_url($item->previewPath())) ?>" alt="<?= e($item->altText ?? '') ?>" loading="lazy" width="160" height="120">
                <?php else: ?>
                    <span class="media-grid__icon" aria-hidden="true">📄</span>
                <?php endif; ?>
                <a class="media-grid__name" href="<?= e(upload_url($item->storedPath)) ?>" target="_blank" rel="noopener"><?= e($item->originalName) ?></a>
                <span class="form-field__hint"><?= e(t('media.size_kb', ['size' => number_format($item->sizeBytes / 1024, 0, ',', '.')])) ?></span>
                <span>
                    <a class="button button--small" href="/admin/media/<?= e($item->id) ?>"><?= e(t('common.edit')) ?></a>
                    <?= partial('admin/partials/confirm-delete', ['action' => "/admin/media/{$item->id}/delete", 'csrfToken' => $csrfToken]) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
