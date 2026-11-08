<?php
/**
 * Logo nhà tài trợ nhóm theo hạng (module sponsors).
 *
 * @var list<\App\Models\SponsorTier> $tiers
 */
?>
<?php if ($tiers === []): ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php endif; ?>
<?php foreach ($tiers as $tier): ?>
    <section class="sponsor-tier">
        <h2><?= e($tier->name) ?></h2>
        <ul class="sponsor-grid">
            <?php foreach ($tier->sponsors as $sponsor): ?>
                <li class="sponsor-grid__item">
                    <?php $logo = '<img src="' . e(upload_url($sponsor->logoPath)) . '"' . srcset_attr($sponsor->logoSmallPath, null, '200px') . ' alt="' . e($sponsor->name) . '" loading="lazy" width="200" height="100">'; ?>
                    <?php if (($sponsor->websiteUrl ?? '') !== ''): ?>
                        <a href="<?= e($sponsor->websiteUrl) ?>" target="_blank" rel="noopener"><?= $logo /* giá trị bên trong đã thoát */ ?></a>
                    <?php else: ?>
                        <?= $logo /* giá trị bên trong đã thoát */ ?>
                    <?php endif; ?>
                    <span class="sponsor-grid__name"><?= e($sponsor->name) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
