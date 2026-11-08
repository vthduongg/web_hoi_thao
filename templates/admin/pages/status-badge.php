<?php
/**
 * Nhãn trạng thái trang; trang xuất bản trong tương lai hiện "Hẹn giờ ...".
 *
 * @var \App\Models\Page $page
 */
?>
<span class="badge badge--<?= e($page->status) ?>">
    <?= e($page->isScheduled() ? t('pages.scheduled', ['time' => format_date($page->publishedAt, 'admin')]) : t('pages.status_' . $page->status)) ?>
</span>
