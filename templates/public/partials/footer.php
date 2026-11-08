<?php
/**
 * Chân trang: liên hệ, nội dung footer, bản quyền, link Past Conferences.
 *
 * @var ?\App\Models\Conference $conference
 */
?>
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div>
                <p><strong><?= e($conference->name ?? '') ?></strong></p>
                <?php if (($conference->footerText ?? '') !== ''): ?>
                    <p><?= e($conference->footerText) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <?php if (($conference->contactPhone ?? '') !== ''): ?>
                    <p><?= e(t('common.phone')) ?>:
                        <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $conference->contactPhone)) ?>"><?= e($conference->contactPhone) ?></a></p>
                <?php endif; ?>
                <?php if (($conference->contactEmail ?? '') !== ''): ?>
                    <p><?= e(t('common.email')) ?>:
                        <a href="mailto:<?= e($conference->contactEmail) ?>"><?= e($conference->contactEmail) ?></a></p>
                <?php endif; ?>
                <p><a href="/past-conferences"><?= e(t('common.past_conferences')) ?></a></p>
            </div>
        </div>
        <p class="site-footer__copyright"><?= e($conference->copyrightText ?? '') ?></p>
    </div>
</footer>
