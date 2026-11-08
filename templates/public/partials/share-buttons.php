<?php
/**
 * Nút chia sẻ mạng xã hội: link thường, không nhúng mã bên ngoài.
 *
 * @var string $shareUrl    đường dẫn đầy đủ của trang
 * @var string $shareTitle
 */
$encodedUrl = rawurlencode($shareUrl);
$encodedTitle = rawurlencode($shareTitle);
?>
<div class="share-buttons">
    <p class="share-buttons__label"><?= e(t('share.title')) ?></p>
    <ul class="share-buttons__list">
        <li><a class="button" href="https://www.facebook.com/sharer/sharer.php?u=<?= e($encodedUrl) ?>" target="_blank" rel="noopener">Facebook</a></li>
        <li><a class="button" href="https://twitter.com/intent/tweet?url=<?= e($encodedUrl) ?>&amp;text=<?= e($encodedTitle) ?>" target="_blank" rel="noopener">X</a></li>
        <li><a class="button" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= e($encodedUrl) ?>" target="_blank" rel="noopener">LinkedIn</a></li>
        <li><button type="button" class="button" data-copy-link
                data-copied-text="<?= e(t('share.copied')) ?>" data-failed-text="<?= e(t('share.copy_failed')) ?>"><?= e(t('share.copy')) ?></button></li>
    </ul>
    <p class="share-buttons__status" data-copy-status aria-live="polite"></p>
</div>
