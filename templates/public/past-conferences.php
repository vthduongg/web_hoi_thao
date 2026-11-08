<?php
/**
 * Trang "Past Conferences": các hội thảo đã lưu trữ, mới nhất trước.
 *
 * @var list<\App\Models\Conference> $archived
 */
?>
<div class="container page-content">
    <h1><?= e(t('archive.title')) ?></h1>
    <?php if ($archived === []): ?>
        <p class="empty-note"><?= e(t('archive.empty')) ?></p>
    <?php else: ?>
        <ul class="past-conferences">
            <?php foreach ($archived as $conference): ?>
                <li>
                    <a href="/<?= e($conference->year) ?>"><strong><?= e($conference->year) ?></strong> — <?= e($conference->name) ?></a>
                    <?php if (($conference->theme ?? '') !== ''): ?>
                        <br><span class="empty-note"><?= e($conference->theme) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
