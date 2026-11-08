<?php
/**
 * Đầu trang: logo + menu.
 *
 * @var ?\App\Models\Conference $conference
 * @var ?string $logoUrl
 */
?>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__logo" href="<?= e(url('/')) ?>">
            <?php if (($logoUrl ?? '') !== ''): ?>
                <img src="<?= e($logoUrl) ?>" alt="<?= e($conference->name ?? '') ?>" width="160" height="56">
            <?php else: ?>
                <span><?= e($conference->name ?? '') ?></span>
            <?php endif; ?>
        </a>
        <?= partial('public/partials/menu') ?>
    </div>
</header>
