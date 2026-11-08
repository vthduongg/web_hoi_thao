<?php
/**
 * Tạo / sửa trang nội dung.
 *
 * @var ?\App\Models\Page $page   null khi tạo mới
 * @var array<string, mixed> $values
 * @var array<string, string> $errors
 * @var bool $conflict          trang đã bị người khác sửa
 * @var ?\App\Models\MediaFile $featuredMedia
 * @var string $csrfToken
 */
$action = $page === null ? '/admin/pages/new' : '/admin/pages/' . $page->id;
$field = static fn (string $name): string => isset($errors[$name]) ? ' form-field--invalid' : '';
?>
<h1><?= e($page === null ? t('pages.create') : t('pages.edit')) ?></h1>

<?php if ($page !== null): ?>
    <p class="page-toolbar">
        <?= partial('admin/pages/status-badge', ['page' => $page]) ?>
        <a class="button button--small" href="/admin/pages/<?= e($page->id) ?>/preview" target="_blank" rel="noopener"><?= e(t('pages.preview')) ?></a>
        <a class="button button--small" href="/admin/pages/<?= e($page->id) ?>/revisions"><?= e(t('pages.revisions')) ?></a>
        <?php if ($page->status === 'published'): ?>
            <a class="button button--small" href="/<?= e($page->slug) ?>" target="_blank" rel="noopener"><?= e(t('pages.view_public')) ?></a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if ($conflict): ?>
    <div class="flash flash--error" role="alert">
        <p><strong><?= e(t('pages.conflict')) ?></strong></p>
        <p><?= e(t('pages.conflict_help')) ?></p>
        <p><a class="button button--small" href="/admin/pages/<?= e($page->id) ?>" target="_blank" rel="noopener"><?= e(t('pages.open_latest')) ?></a></p>
    </div>
<?php endif; ?>
<?= partial('admin/partials/form-errors', ['errors' => $errors]) ?>
<div class="flash flash--error" data-alt-warning hidden role="alert" data-template="<?= e(t('pages.alt_warning', ['count' => '{count}'])) ?>"></div>

<form method="post" action="<?= e($action) ?>" data-page-form>
    <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="version" value="<?= e($page->version ?? 1) ?>">

    <div class="admin-card">
        <div class="form-field<?= $field('title') ?>">
            <label for="title"><?= e(t('pages.field_title')) ?></label>
            <input type="text" id="title" name="title" value="<?= e($values['title']) ?>" required maxlength="255">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'title']) ?>
        </div>
        <div class="form-field<?= $field('slug') ?>">
            <label for="slug"><?= e(t('pages.field_slug')) ?></label>
            <input type="text" id="slug" name="slug" value="<?= e($values['slug']) ?>" maxlength="150" pattern="[a-z0-9-]*">
            <p class="form-field__hint"><?= e(t('pages.slug_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'slug']) ?>
        </div>
        <div class="form-field">
            <label for="body_html"><?= e(t('pages.field_body')) ?></label>
            <textarea id="body_html" name="body_html" data-rich-editor
                      data-library-label="<?= e(t('pages.insert_from_library')) ?>"
                      data-attraction-label="<?= e(t('pages.insert_attraction')) ?>"
                      data-attraction-title="<?= e(t('pages.attraction_title_placeholder')) ?>"
                      data-attraction-description="<?= e(t('pages.attraction_description_placeholder')) ?>"><?= e($values['body_html']) ?></textarea>
            <p class="form-field__hint"><?= e(t('pages.embed_hint')) ?></p>
        </div>
        <div class="form-field<?= $field('module') ?>">
            <label for="module"><?= e(t('pages.field_module')) ?></label>
            <select id="module" name="module">
                <?php foreach (\App\Services\PageService::MODULES as $module): ?>
                    <option value="<?= e($module) ?>" <?= $values['module'] === $module ? 'selected' : '' ?>><?= e(t('pages.module_' . $module)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field<?= $field('tags') ?>">
            <label for="tags"><?= e(t('pages.field_tags')) ?></label>
            <input type="text" id="tags" name="tags" value="<?= e(implode(', ', $values['tags'])) ?>">
            <p class="form-field__hint"><?= e(t('pages.tags_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'tags']) ?>
        </div>
    </div>

    <div class="admin-card">
        <h2><?= e(t('pages.field_status')) ?></h2>
        <div class="form-field<?= $field('status') ?>">
            <label for="status"><?= e(t('pages.field_status')) ?></label>
            <select id="status" name="status">
                <?php foreach (\App\Services\PageService::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= $values['status'] === $status ? 'selected' : '' ?>><?= e(t('pages.status_' . $status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field<?= $field('published_at') ?>">
            <label for="published_at"><?= e(t('pages.field_published_at')) ?></label>
            <input type="datetime-local" id="published_at" name="published_at" value="<?= e($values['published_at']) ?>">
            <?php if (local_input_to_display($values['published_at']) !== ''): ?>
                <p class="form-field__hint"><?= e(t('pages.published_at_current', ['time' => local_input_to_display($values['published_at'])])) ?></p>
            <?php endif; ?>
            <p class="form-field__hint"><?= e(t('pages.published_at_hint')) ?></p>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'published_at']) ?>
        </div>
    </div>

    <div class="admin-card">
        <h2><?= e(t('pages.seo')) ?></h2>
        <div class="form-field<?= $field('seo_title') ?>">
            <label for="seo_title"><?= e(t('pages.field_seo_title')) ?></label>
            <input type="text" id="seo_title" name="seo_title" value="<?= e($values['seo_title']) ?>" maxlength="255">
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'seo_title']) ?>
        </div>
        <div class="form-field<?= $field('seo_description') ?>">
            <label for="seo_description"><?= e(t('pages.field_seo_description')) ?></label>
            <textarea id="seo_description" name="seo_description" maxlength="500" rows="3"><?= e($values['seo_description']) ?></textarea>
            <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => 'seo_description']) ?>
        </div>
        <?= partial('admin/partials/media-field', [
            'name' => 'featured_media_id',
            'label' => t('pages.field_featured'),
            'media' => $featuredMedia,
        ]) ?>
    </div>

    <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
    <a class="button" href="/admin/pages"><?= e(t('common.cancel')) ?></a>
</form>
