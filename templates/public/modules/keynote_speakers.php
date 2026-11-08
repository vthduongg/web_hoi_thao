<?php
/**
 * Thẻ diễn giả chính (module keynote_speakers).
 *
 * @var list<\App\Models\KeynoteSpeaker> $speakers
 */
?>
<?php if ($speakers === []): ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php else: ?>
    <div class="card-grid speakers">
        <?php foreach ($speakers as $speaker): ?>
            <article class="card speaker-card">
                <?php if ($speaker->photoPath !== null): ?>
                    <img class="speaker-card__photo" src="<?= e(upload_url($speaker->photoPath)) ?>" alt="<?= e($speaker->displayName()) ?>" loading="lazy" width="240" height="240">
                <?php endif; ?>
                <h2 class="speaker-card__name"><?= e($speaker->displayName()) ?></h2>
                <?php if (($speaker->affiliation ?? '') !== '' || ($speaker->country ?? '') !== ''): ?>
                    <p class="speaker-card__affiliation"><?= e(implode(', ', array_filter([$speaker->affiliation, $speaker->country]))) ?></p>
                <?php endif; ?>
                <?php if (($speaker->talkTitle ?? '') !== ''): ?>
                    <p class="speaker-card__talk"><strong><?= e(t('speakers.talk')) ?>:</strong> <?= e($speaker->talkTitle) ?></p>
                <?php endif; ?>
                <div class="speaker-card__bio"><?= $speaker->bioHtml /* đã lọc bằng HTML Purifier khi lưu */ ?></div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
