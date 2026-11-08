<?php
/**
 * Một dòng trong cây menu (nút di chuyển, nhãn, đích, Sửa/Xoá).
 *
 * @var \App\Models\MenuItem $item
 * @var string $target   mô tả đích: /slug, link ngoài, hoặc "chỉ là nhóm"
 * @var string $csrfToken
 */
?>
<span class="sortable-controls">
    <button type="button" class="button button--small" data-move="up" aria-label="<?= e(t('common.move_up') . ': ' . $item->label) ?>">▲</button>
    <button type="button" class="button button--small" data-move="down" aria-label="<?= e(t('common.move_down') . ': ' . $item->label) ?>">▼</button>
    <button type="button" class="button button--small" data-move="in" aria-label="<?= e(t('common.indent') . ': ' . $item->label) ?>">→</button>
    <button type="button" class="button button--small" data-move="out" aria-label="<?= e(t('common.outdent') . ': ' . $item->label) ?>">←</button>
</span>
<span class="crud-list__main">
    <strong data-item-label><?= e($item->label) ?></strong>
    <span class="crud-list__cell"><?= e($target) ?></span>
</span>
<span class="crud-list__actions">
    <a class="button button--small" href="/admin/menu/<?= e($item->id) ?>"><?= e(t('common.edit')) ?></a>
    <?= partial('admin/partials/confirm-delete', ['action' => '/admin/menu/' . $item->id . '/delete', 'csrfToken' => $csrfToken]) ?>
</span>
