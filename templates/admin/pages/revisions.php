<?php
/**
 * Lịch sử phiên bản của trang.
 *
 * @var \App\Models\Page $page
 * @var list<array{revision_no: int, title: string, created_at: string, author: ?string}> $revisions
 * @var string $csrfToken
 */
?>
<h1><?= e(t('pages.revisions')) ?>: <?= e($page->title) ?></h1>
<p><a class="button" href="/admin/pages/<?= e($page->id) ?>"><?= e(t('common.back')) ?></a></p>
<div class="table-scroll">
    <table class="admin-table">
        <thead>
            <tr>
                <th scope="col"><?= e(t('pages.revision_no')) ?></th>
                <th scope="col"><?= e(t('pages.field_title')) ?></th>
                <th scope="col"><?= e(t('pages.revision_author')) ?></th>
                <th scope="col"><?= e(t('pages.revision_time')) ?></th>
                <th scope="col"><span class="visually-hidden"><?= e(t('users.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($revisions as $index => $revision): ?>
                <tr>
                    <td>#<?= e($revision['revision_no']) ?></td>
                    <td><?= e($revision['title']) ?></td>
                    <td><?= e($revision['author'] ?? t('dashboard.system')) ?></td>
                    <td><?= e(format_date($revision['created_at'], 'admin')) ?></td>
                    <td class="admin-table__actions">
                        <?php if ($index > 0): ?>
                            <form method="post" action="/admin/pages/<?= e($page->id) ?>/revisions/<?= e($revision['revision_no']) ?>/restore"
                                  class="inline-form" data-confirm="<?= e(t('pages.confirm_restore')) ?>">
                                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="button button--small"><?= e(t('pages.restore')) ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
