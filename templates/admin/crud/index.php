<?php
/**
 * Danh sách dùng chung cho dữ liệu có cấu trúc: sắp xếp (kéo thả hoặc ▲/▼), Hiện/Ẩn, Sửa, Xoá.
 *
 * @var string $pageTitle
 * @var string $basePath
 * @var list<array{id: int, label: string, cells: array<string, string>, visible: ?bool, links: list<array{0: string, 1: string}>}> $items
 * @var list<array{0: string, 1: string}> $headerLinks
 * @var string $csrfToken
 */
?>
<h1><?= e($pageTitle) ?></h1>
<p>
    <a class="button button--primary" href="<?= e($basePath) ?>/new"><?= e(t('common.add')) ?></a>
    <?php foreach ($headerLinks as [$text, $href]): ?>
        <a class="button" href="<?= e($href) ?>"><?= e($text) ?></a>
    <?php endforeach; ?>
</p>

<?php if ($items === []): ?>
    <p><?= e(t('common.empty')) ?></p>
<?php else: ?>
    <p class="form-field__hint"><?= e(t('crud.sort_hint')) ?></p>
    <ol class="crud-list" data-sortable data-sortable-url="<?= e($basePath) ?>/reorder" data-csrf="<?= e($csrfToken) ?>"
        data-saved-text="<?= e(t('crud.order_saved')) ?>" data-error-text="<?= e(t('crud.reorder_invalid')) ?>">
        <?php foreach ($items as $item): ?>
            <li class="crud-list__item<?= $item['visible'] === false ? ' crud-list__item--hidden' : '' ?>" data-sortable-item data-id="<?= e($item['id']) ?>">
                <span class="sortable-controls">
                    <button type="button" class="button button--small" data-move="up" aria-label="<?= e(t('common.move_up') . ': ' . $item['label']) ?>">▲</button>
                    <button type="button" class="button button--small" data-move="down" aria-label="<?= e(t('common.move_down') . ': ' . $item['label']) ?>">▼</button>
                </span>
                <span class="crud-list__main">
                    <strong data-item-label><?= e($item['label']) ?></strong>
                    <?php foreach ($item['cells'] as $title => $value): ?>
                        <span class="crud-list__cell"><span class="visually-hidden"><?= e($title) ?>: </span><?= e($value) ?></span>
                    <?php endforeach; ?>
                    <?php if ($item['visible'] === false): ?>
                        <span class="badge badge--hidden"><?= e(t('common.hidden')) ?></span>
                    <?php endif; ?>
                </span>
                <span class="crud-list__actions">
                    <?php foreach ($item['links'] as [$text, $href]): ?>
                        <a class="button button--small" href="<?= e($href) ?>"><?= e($text) ?></a>
                    <?php endforeach; ?>
                    <a class="button button--small" href="<?= e($basePath . '/' . $item['id']) ?>"><?= e(t('common.edit')) ?></a>
                    <?php if ($item['visible'] !== null): ?>
                        <form method="post" action="<?= e($basePath . '/' . $item['id']) ?>/toggle-visibility" class="inline-form">
                            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button button--small"><?= e($item['visible'] ? t('crud.hide') : t('crud.show')) ?></button>
                        </form>
                    <?php endif; ?>
                    <?= partial('admin/partials/confirm-delete', ['action' => $basePath . '/' . $item['id'] . '/delete', 'csrfToken' => $csrfToken]) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="form-field__hint" data-sortable-status aria-live="polite"></p>
<?php endif; ?>
