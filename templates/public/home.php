<?php
/**
 * Trang chủ.
 *
 * @var \App\Models\Conference $conference
 * @var string $introHtml                         đoạn giới thiệu (đã lọc HTML khi lưu)
 * @var ?\App\Models\ImportantDate $conferenceDay
 * @var ?\App\Models\ImportantDate $abstractDeadline
 * @var ?\App\Models\ImportantDate $fullPaperDeadline
 * @var list<\App\Models\Slide> $slides
 * @var list<\App\Models\Highlight> $highlights
 * @var ?string $callForPapersUrl
 * @var ?string $paperSubmissionUrl
 */
?>
<?= partial('public/partials/slider', ['slides' => $slides]) ?>

<section class="home-hero">
    <div class="container">
        <h1 class="home-hero__title"><?= e($conference->name) ?></h1>
        <?php if (($conference->theme ?? '') !== ''): ?>
            <p class="home-hero__theme"><?= e($conference->theme) ?></p>
        <?php endif; ?>
        <div class="home-hero__actions">
            <?= partial('public/partials/submit-button') ?>
            <?php if ($callForPapersUrl !== null): ?>
                <a class="button" href="<?= e($callForPapersUrl) ?>"><?= e(t('home.call_for_papers')) ?></a>
            <?php endif; ?>
            <?php if ($paperSubmissionUrl !== null): ?>
                <a class="button" href="<?= e($paperSubmissionUrl) ?>"><?= e(t('home.paper_submission')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container home-info" aria-labelledby="key-info-title">
    <h2 id="key-info-title" class="visually-hidden"><?= e(t('home.key_information')) ?></h2>
    <dl class="info-grid">
        <?php if ($conferenceDay !== null): ?>
            <div class="info-grid__item">
                <dt><?= e(t('home.date')) ?></dt>
                <dd><?= partial('public/partials/date-value', ['date' => $conferenceDay]) ?></dd>
            </div>
        <?php endif; ?>
        <?php if (($conference->venue ?? '') !== ''): ?>
            <div class="info-grid__item">
                <dt><?= e(t('home.venue')) ?></dt>
                <dd><?= e($conference->venue) ?></dd>
            </div>
        <?php endif; ?>
        <?php if (($conference->conferenceLanguage ?? '') !== ''): ?>
            <div class="info-grid__item">
                <dt><?= e(t('home.language')) ?></dt>
                <dd><?= e($conference->conferenceLanguage) ?></dd>
            </div>
        <?php endif; ?>
        <?php if ($abstractDeadline !== null): ?>
            <div class="info-grid__item">
                <dt><?= e(t('home.abstract_deadline')) ?></dt>
                <dd><?= partial('public/partials/date-value', ['date' => $abstractDeadline]) ?></dd>
            </div>
        <?php endif; ?>
        <?php if ($fullPaperDeadline !== null): ?>
            <div class="info-grid__item">
                <dt><?= e(t('home.full_paper_deadline')) ?></dt>
                <dd><?= partial('public/partials/date-value', ['date' => $fullPaperDeadline]) ?></dd>
            </div>
        <?php endif; ?>
    </dl>
</section>

<?php if ($introHtml !== ''): ?>
    <section class="container page-content home-intro">
        <?= $introHtml /* đã lọc bằng HTML Purifier khi lưu */ ?>
    </section>
<?php endif; ?>

<?php if ($highlights !== []): ?>
    <section class="container highlights" aria-label="<?= e(t('home.highlights')) ?>">
        <div class="card-grid">
            <?php foreach ($highlights as $highlight): ?>
                <article class="card">
                    <h2 class="card__title">
                        <?php if (($highlight->linkUrl ?? '') !== ''): ?>
                            <a href="<?= e($highlight->linkUrl) ?>"><?= e($highlight->title) ?></a>
                        <?php else: ?>
                            <?= e($highlight->title) ?>
                        <?php endif; ?>
                    </h2>
                    <?php if (($highlight->bodyText ?? '') !== ''): ?>
                        <p><?= e($highlight->bodyText) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
