<?php
/**
 * Thùng rác: mục đã xoá, ngày tự xoá vĩnh viễn, Khôi phục / Xoá vĩnh viễn.
 *
 * @var list<array{key: string, type: string, id: int, label: string, type_label: string, deleted_at: string, purge_at: string, deleted_by: ?string}> $items
 * @var bool $canPurge   chỉ quản trị viên
 * @var string $csrfToken
 */
?>
<h1><?= e(t('trash.title')) ?></h1>
<p class="form-field__hint"><?= e(t('trash.help')) ?></p>

<?php if ($items === []): ?>
    <p><?= e(t('trash.empty')) ?></p>
<?php else: ?>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('trash.item')) ?></th>
                    <th scope="col"><?= e(t('trash.type')) ?></th>
                    <th scope="col"><?= e(t('trash.deleted_at')) ?></th>
                    <th scope="col"><?= e(t('trash.purge_at')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('users.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['label']) ?></td>
                        <td><?= e($item['type_label']) ?></td>
                        <td><?= e(format_date($item['deleted_at'], 'admin')) ?><?= $item['deleted_by'] !== null ? ' — ' . e($item['deleted_by']) : '' ?></td>
                        <td><?= e(format_date($item['purge_at'], 'admin_date')) ?></td>
                        <td class="admin-table__actions">
                            <form method="post" action="/admin/trash/<?= e($item['type']) ?>/<?= e($item['id']) ?>/restore" class="inline-form">
                                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                                <button type="submit" class="button button--small"><?= e(t('trash.restore')) ?></button>
                            </form>
                            <?php if ($canPurge): ?>
                                <?= partial('admin/partials/confirm-delete', [
                                    'action' => "/admin/trash/{$item['type']}/{$item['id']}/purge",
                                    'csrfToken' => $csrfToken,
                                    'label' => t('trash.purge'),
                                    'message' => t('trash.confirm_purge'),
                                ]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
