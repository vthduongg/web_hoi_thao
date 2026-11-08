<?php
/**
 * Danh sách trang nội dung.
 *
 * @var list<\App\Models\Page> $pages
 * @var array{status: string, tag: string, q: string} $filters
 * @var string $csrfToken
 */
?>
<h1><?= e(t('pages.title')) ?></h1>
<p><a class="button button--primary" href="/admin/pages/new"><?= e(t('pages.create')) ?></a></p>

<form method="get" action="/admin/pages" class="admin-card filter-bar">
    <div class="form-field">
        <label for="filter-status"><?= e(t('pages.filter_status')) ?></label>
        <select id="filter-status" name="status">
            <option value=""><?= e(t('media.kind_all')) ?></option>
            <?php foreach (['draft', 'published', 'hidden'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(t('pages.status_' . $status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label for="filter-tag"><?= e(t('pages.filter_tag')) ?></label>
        <input type="text" id="filter-tag" name="tag" value="<?= e($filters['tag']) ?>">
    </div>
    <div class="form-field">
        <label for="filter-q"><?= e(t('pages.filter_q')) ?></label>
        <input type="text" id="filter-q" name="q" value="<?= e($filters['q']) ?>">
    </div>
    <button type="submit" class="button"><?= e(t('pages.filter')) ?></button>
</form>

<?php if ($pages === []): ?>
    <p><?= e(t('pages.empty')) ?></p>
<?php else: ?>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('pages.field_title')) ?></th>
                    <th scope="col"><?= e(t('pages.field_slug')) ?></th>
                    <th scope="col"><?= e(t('pages.field_status')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('users.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><a href="/admin/pages/<?= e($page->id) ?>"><?= e($page->title) ?></a></td>
                        <td>/<?= e($page->slug) ?></td>
                        <td><?= partial('admin/pages/status-badge', ['page' => $page]) ?></td>
                        <td class="admin-table__actions">
                            <a class="button button--small" href="/admin/pages/<?= e($page->id) ?>/preview" target="_blank" rel="noopener"><?= e(t('pages.preview')) ?></a>
                            <?= partial('admin/partials/confirm-delete', ['action' => "/admin/pages/{$page->id}/delete", 'csrfToken' => $csrfToken]) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
