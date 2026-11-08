<?php
/**
 * Menu chính (tối đa 2 cấp). Dữ liệu do MenuRepository::treeForPublic() chuẩn bị.
 *
 * @var list<array{label: string, href: ?string, external: bool, children: list<array{label: string, href: ?string, external: bool}>}> $menuTree
 * @var string $currentPath
 */
$menuTree ??= [];
?>
<button type="button" class="button menu-toggle" data-menu-toggle aria-expanded="false" aria-controls="main-menu">
    <?= e(t('common.menu')) ?>
</button>
<nav class="main-menu" id="main-menu" data-main-menu aria-label="<?= e(t('common.menu')) ?>">
    <ul class="main-menu__list">
        <?php foreach ($menuTree as $index => $item): ?>
            <li class="main-menu__item">
                <?php if ($item['children'] !== []): ?>
                    <button type="button" class="main-menu__link" data-submenu-toggle aria-expanded="false" aria-controls="submenu-<?= e($index) ?>">
                        <?= e($item['label']) ?> ▾
                    </button>
                    <ul class="main-menu__submenu" id="submenu-<?= e($index) ?>">
                        <?php foreach ($item['children'] as $child): ?>
                            <li>
                                <a class="main-menu__link" href="<?= e($child['href']) ?>"
                                    <?= $child['external'] ? 'target="_blank" rel="noopener"' : '' ?>
                                    <?= ($child['href'] === ($currentPath ?? '')) ? 'aria-current="page"' : '' ?>><?= e($child['label']) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <a class="main-menu__link" href="<?= e($item['href']) ?>"
                        <?= $item['external'] ? 'target="_blank" rel="noopener"' : '' ?>
                        <?= ($item['href'] === ($currentPath ?? '')) ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
