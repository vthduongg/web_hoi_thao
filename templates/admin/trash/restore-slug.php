<?php
/**
 * Khôi phục trang có đường dẫn đã bị trang khác dùng: nhập đường dẫn mới.
 *
 * @var string $type
 * @var int $id
 * @var string $slug
 * @var string $csrfToken
 */
?>
<h1><?= e(t('trash.restore')) ?></h1>
<div class="admin-card">
    <div class="flash flash--error" role="alert"><?= e(t('trash.slug_conflict')) ?></div>
    <form method="post" action="/admin/trash/<?= e($type) ?>/<?= e($id) ?>/restore">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field">
            <label for="slug"><?= e(t('pages.field_slug')) ?></label>
            <input type="text" id="slug" name="slug" value="<?= e($slug) ?>" required maxlength="150" pattern="[a-z0-9-]+">
            <p class="form-field__hint"><?= e(t('pages.slug_hint')) ?></p>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('trash.restore')) ?></button>
        <a class="button" href="/admin/trash"><?= e(t('common.cancel')) ?></a>
    </form>
</div>
