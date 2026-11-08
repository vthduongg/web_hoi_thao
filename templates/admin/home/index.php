<?php
/**
 * Quản lý trang chủ: đoạn giới thiệu, lối vào slider và khối nổi bật.
 *
 * @var string $introHtml
 * @var int $slideCount
 * @var int $highlightCount
 * @var string $csrfToken
 */
?>
<h1><?= e(t('home.title')) ?></h1>

<div class="admin-card">
    <h2><?= e(t('home.sections')) ?></h2>
    <p>
        <a class="button" href="/admin/home/slides"><?= e(t('slides.title')) ?> (<?= e($slideCount) ?>)</a>
        <a class="button" href="/admin/home/highlights"><?= e(t('highlights.title')) ?> (<?= e($highlightCount) ?>)</a>
    </p>
    <p class="form-field__hint"><?= e(t('home.settings_hint')) ?></p>
</div>

<div class="admin-card">
    <h2><?= e(t('home.intro')) ?></h2>
    <div class="flash flash--error" data-alt-warning hidden role="alert" data-template="<?= e(t('pages.alt_warning', ['count' => '{count}'])) ?>"></div>
    <form method="post" action="/admin/home/intro">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field">
            <label for="home_intro_html"><?= e(t('home.intro')) ?></label>
            <textarea id="home_intro_html" name="home_intro_html" data-rich-editor="compact"
                      data-library-label="<?= e(t('pages.insert_from_library')) ?>"><?= e($introHtml) ?></textarea>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
    </form>
</div>
