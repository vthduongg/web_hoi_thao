<?php
/**
 * Khối "Related pages" (FR-013). Ẩn khi không có trang liên quan.
 *
 * @var list<\App\Models\Page> $relatedPages
 */
?>
<?php if ($relatedPages !== []): ?>
    <aside class="container related-pages" aria-labelledby="related-pages-title">
        <h2 id="related-pages-title"><?= e(t('related.title')) ?></h2>
        <ul class="related-pages__list">
            <?php foreach ($relatedPages as $related): ?>
                <li><a href="<?= e(url('/' . $related->slug)) ?>"><?= e($related->title) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </aside>
<?php endif; ?>
