<?php
/**
 * Trang nội dung công khai.
 *
 * @var \App\Models\Page $page
 * @var ?string $moduleTemplate     template khối dữ liệu (mốc thời gian, ban...) hoặc null
 * @var array<string, mixed> $moduleData
 * @var list<\App\Models\Page> $relatedPages
 * @var string $shareUrl
 */
?>
<article class="container page-content">
    <h1><?= e($page->title) ?></h1>

    <?= lazy_images($page->bodyHtml) /* đã lọc bằng HTML Purifier khi lưu */ ?>

    <?php if ($moduleTemplate !== null): ?>
        <?= partial($moduleTemplate, $moduleData) ?>
    <?php endif; ?>

    <div class="page-actions">
        <?= partial('public/partials/submit-button') ?>
    </div>

    <?= partial('public/partials/share-buttons', ['shareUrl' => $shareUrl, 'shareTitle' => $page->title]) ?>
</article>

<?= partial('public/partials/related-pages', ['relatedPages' => $relatedPages]) ?>
