<?php
/**
 * Xác nhận lần hai khi xoá trang đang có trong menu.
 *
 * @var \App\Models\Page $page
 * @var list<string> $menuLabels
 * @var string $csrfToken
 */
?>
<h1><?= e(t('common.delete')) ?>: <?= e($page->title) ?></h1>
<div class="admin-card">
    <p><?= e(t('pages.delete_in_menu')) ?></p>
    <ul>
        <?php foreach ($menuLabels as $label): ?>
            <li><?= e($label) ?></li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="/admin/pages/<?= e($page->id) ?>/delete" class="inline-form">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="confirmed" value="1">
        <button type="submit" class="button button--danger"><?= e(t('pages.delete_confirm_button')) ?></button>
    </form>
    <a class="button" href="/admin/pages"><?= e(t('common.cancel')) ?></a>
</div>
