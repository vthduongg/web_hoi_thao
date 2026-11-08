<?php
/**
 * Cây menu 2 cấp: kéo thả hoặc ▲/▼ để sắp xếp, →/← để đổi cấp.
 *
 * @var list<array{item: \App\Models\MenuItem, target: string, children: list<array{item: \App\Models\MenuItem, target: string}>}> $tree
 * @var string $csrfToken
 */
?>
<h1><?= e(t('menu.title')) ?></h1>
<p><a class="button button--primary" href="/admin/menu/new"><?= e(t('menu.create')) ?></a></p>
<p class="form-field__hint"><?= e(t('menu.help')) ?></p>

<?php if ($tree === []): ?>
    <p><?= e(t('common.empty')) ?></p>
<?php else: ?>
    <ol class="crud-list menu-tree" data-sortable data-max-depth="2" data-sortable-url="/admin/menu/reorder" data-csrf="<?= e($csrfToken) ?>"
        data-saved-text="<?= e(t('crud.order_saved')) ?>" data-error-text="<?= e(t('crud.reorder_invalid')) ?>" data-depth-error="<?= e(t('menu.too_deep')) ?>">
        <?php foreach ($tree as $node): ?>
            <li class="crud-list__item menu-tree__item" data-sortable-item data-id="<?= e($node['item']->id) ?>">
                <?= partial('admin/menu/item-row', ['item' => $node['item'], 'target' => $node['target'], 'csrfToken' => $csrfToken]) ?>
                <ol class="menu-tree__children" data-sortable-children>
                    <?php foreach ($node['children'] as $child): ?>
                        <li class="crud-list__item menu-tree__item" data-sortable-item data-id="<?= e($child['item']->id) ?>">
                            <?= partial('admin/menu/item-row', ['item' => $child['item'], 'target' => $child['target'], 'csrfToken' => $csrfToken]) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="form-field__hint" data-sortable-status aria-live="polite"></p>
<?php endif; ?>
