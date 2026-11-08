<?php
/**
 * Xác nhận lần hai khi xoá tệp đang được dùng.
 *
 * @var \App\Models\MediaFile $media
 * @var list<string> $usages
 * @var string $csrfToken
 */
?>
<h1><?= e(t('common.delete')) ?>: <?= e($media->originalName) ?></h1>
<div class="admin-card">
    <p><strong><?= e(t('media.in_use')) ?></strong></p>
    <ul>
        <?php foreach ($usages as $usage): ?>
            <li><?= e($usage) ?></li>
        <?php endforeach; ?>
    </ul>
    <p><?= e(t('media.in_use_warning')) ?></p>
    <form method="post" action="/admin/media/<?= e($media->id) ?>/delete" class="inline-form">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="confirmed" value="1">
        <button type="submit" class="button button--danger"><?= e(t('media.delete_anyway')) ?></button>
    </form>
    <a class="button" href="/admin/media"><?= e(t('common.cancel')) ?></a>
</div>
