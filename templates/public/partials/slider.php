<?php
/**
 * Slider ảnh trang chủ (slider.js tự chuyển ảnh; khi tắt JavaScript chỉ hiện ảnh đầu).
 *
 * @var list<\App\Models\Slide> $slides
 */
?>
<?php if ($slides !== []): ?>
    <section class="slider" data-slider data-interval="5000" aria-roledescription="carousel" aria-label="<?= e(t('slider.label')) ?>">
        <?php foreach ($slides as $index => $slide): ?>
            <figure class="slider__slide<?= $index === 0 ? ' is-active' : '' ?>" data-slide data-index="<?= e($index) ?>"
                    aria-roledescription="slide" aria-label="<?= e(($index + 1) . ' / ' . count($slides)) ?>"<?= $index === 0 ? '' : ' aria-hidden="true"' ?>>
                <?php $image = '<img src="' . e(upload_url($slide->largeImagePath ?? $slide->imagePath)) . '" alt="' . e($slide->altText) . '"'
                    . ($index === 0 ? '' : ' loading="lazy"') . ' width="1200" height="450">'; ?>
                <?php if (($slide->linkUrl ?? '') !== ''): ?>
                    <a href="<?= e($slide->linkUrl) ?>"><?= $image /* giá trị bên trong đã thoát */ ?></a>
                <?php else: ?>
                    <?= $image /* giá trị bên trong đã thoát */ ?>
                <?php endif; ?>
                <?php if (($slide->title ?? '') !== ''): ?>
                    <figcaption class="slider__caption"><?= e($slide->title) ?></figcaption>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
        <?php if (count($slides) > 1): ?>
            <button type="button" class="button slider__toggle" data-slider-toggle
                    data-pause-text="<?= e(t('slider.pause')) ?>" data-play-text="<?= e(t('slider.play')) ?>"><?= e(t('slider.pause')) ?></button>
        <?php endif; ?>
    </section>
<?php endif; ?>
