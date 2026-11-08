<?php
/**
 * Nhật ký hoạt động (chỉ đọc), 50 dòng mỗi trang.
 *
 * @var list<array<string, mixed>> $rows
 * @var bool $hasNextPage
 * @var int $page
 * @var array{user: string, action: string, from: string, to: string} $filters
 * @var list<array{id: int, full_name: string}> $users
 */
$actions = ['create', 'update', 'delete', 'restore', 'purge', 'publish', 'unpublish', 'login', 'login_failed', 'logout', 'account_change', 'copy_conference', 'import', 'backup'];
$query = static fn (int $toPage): string => '?' . http_build_query(array_filter($filters + ['page' => $toPage]));
?>
<h1><?= e(t('nav.activity')) ?></h1>

<form method="get" action="/admin/activity" class="admin-card filter-bar">
    <div class="form-field">
        <label for="user"><?= e(t('dashboard.who')) ?></label>
        <select id="user" name="user">
            <option value=""><?= e(t('media.kind_all')) ?></option>
            <?php foreach ($users as $user): ?>
                <option value="<?= e($user['id']) ?>" <?= $filters['user'] === (string) $user['id'] ? 'selected' : '' ?>><?= e($user['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label for="action"><?= e(t('dashboard.what')) ?></label>
        <select id="action" name="action">
            <option value=""><?= e(t('media.kind_all')) ?></option>
            <?php foreach ($actions as $action): ?>
                <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e(t('action.' . $action)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label for="from"><?= e(t('activity.from')) ?></label>
        <input type="date" id="from" name="from" value="<?= e($filters['from']) ?>">
    </div>
    <div class="form-field">
        <label for="to"><?= e(t('activity.to')) ?></label>
        <input type="date" id="to" name="to" value="<?= e($filters['to']) ?>">
    </div>
    <button type="submit" class="button"><?= e(t('pages.filter')) ?></button>
</form>

<?php if ($rows === []): ?>
    <p><?= e(t('common.empty')) ?></p>
<?php else: ?>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('dashboard.time')) ?></th>
                    <th scope="col"><?= e(t('dashboard.who')) ?></th>
                    <th scope="col"><?= e(t('dashboard.what')) ?></th>
                    <th scope="col"><?= e(t('dashboard.item')) ?></th>
                    <th scope="col"><?= e(t('activity.ip')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e(format_date($row['created_at'], 'admin')) ?></td>
                        <td><?= e($row['author'] ?? t('dashboard.system')) ?></td>
                        <td><?= e(t('action.' . $row['action'])) ?></td>
                        <td><?= e($row['entity_label']) ?></td>
                        <td><?= e($row['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<p>
    <?php if ($page > 1): ?>
        <a class="button button--small" href="<?= e($query($page - 1)) ?>"><?= e(t('activity.newer')) ?></a>
    <?php endif; ?>
    <?php if ($hasNextPage): ?>
        <a class="button button--small" href="<?= e($query($page + 1)) ?>"><?= e(t('activity.older')) ?></a>
    <?php endif; ?>
</p>
