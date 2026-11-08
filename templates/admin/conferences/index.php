<?php
/**
 * Các kỳ hội thảo: đặt hiện hành, sao chép sang năm mới.
 *
 * @var list<\App\Models\Conference> $conferences
 * @var array<string, string> $errors
 * @var ?int $copySourceId   hội thảo vừa thử sao chép (để hiện lỗi đúng chỗ)
 * @var string $csrfToken
 */
?>
<h1><?= e(t('nav.conferences')) ?></h1>
<p class="form-field__hint"><?= e(t('conferences.help')) ?></p>

<?php foreach ($conferences as $conference): ?>
    <section class="admin-card">
        <h2>
            <?= e($conference->year) ?> — <?= e($conference->name) ?>
            <?php if ($conference->isCurrent): ?>
                <span class="badge badge--published"><?= e(t('conferences.current')) ?></span>
            <?php elseif ($conference->hasBeenCurrent): ?>
                <span class="badge"><?= e(t('conferences.archived')) ?></span>
            <?php else: ?>
                <span class="badge"><?= e(t('conferences.not_published')) ?></span>
            <?php endif; ?>
        </h2>
        <p>
            <?php if (!$conference->isCurrent): ?>
                <form method="post" action="/admin/conferences/<?= e($conference->id) ?>/make-current" class="inline-form"
                      data-confirm="<?= e(t('conferences.confirm_make_current', ['name' => $conference->name])) ?>">
                    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button button--small button--primary"><?= e(t('conferences.make_current')) ?></button>
                </form>
            <?php endif; ?>
            <?php if ($conference->hasBeenCurrent && !$conference->isCurrent): ?>
                <a class="button button--small" href="/<?= e($conference->year) ?>" target="_blank" rel="noopener"><?= e(t('conferences.view_archive')) ?></a>
            <?php endif; ?>
        </p>
        <form method="post" action="/admin/conferences/<?= e($conference->id) ?>/copy" class="filter-bar">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <div class="form-field<?= $copySourceId === $conference->id && isset($errors['year']) ? ' form-field--invalid' : '' ?>">
                <label for="year-<?= e($conference->id) ?>"><?= e(t('conferences.copy_to_year')) ?></label>
                <input type="number" id="year-<?= e($conference->id) ?>" name="year" min="1000" max="9999" value="<?= e($conference->year + 1) ?>">
                <?php if ($copySourceId === $conference->id): ?>
                    <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'year']) ?>
                <?php endif; ?>
            </div>
            <button type="submit" class="button"><?= e(t('conferences.copy')) ?></button>
        </form>
    </section>
<?php endforeach; ?>
