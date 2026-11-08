<?php
/**
 * Bảng điều khiển.
 *
 * @var string $userName
 */
?>
<h1><?= e(t('dashboard.greeting', ['name' => $userName])) ?></h1>
<div class="admin-card">
    <h2><?= e(t('dashboard.shortcuts')) ?></h2>
    <p>
        <a class="button" href="/admin/pages"><?= e(t('nav.pages')) ?></a>
        <a class="button" href="/admin/dates"><?= e(t('nav.dates')) ?></a>
        <a class="button" href="/admin/media"><?= e(t('nav.media')) ?></a>
    </p>
</div>
