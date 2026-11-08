<?php
/**
 * Danh sách tài khoản.
 *
 * @var list<\App\Models\User> $users
 * @var string $csrfToken
 */
?>
<h1><?= e(t('users.title')) ?></h1>
<p><a class="button button--primary" href="/admin/users/new"><?= e(t('users.create')) ?></a></p>
<div class="table-scroll">
    <table class="admin-table">
        <thead>
            <tr>
                <th scope="col"><?= e(t('users.full_name')) ?></th>
                <th scope="col"><?= e(t('users.email')) ?></th>
                <th scope="col"><?= e(t('users.role')) ?></th>
                <th scope="col"><?= e(t('users.status')) ?></th>
                <th scope="col"><?= e(t('users.last_login')) ?></th>
                <th scope="col"><span class="visually-hidden"><?= e(t('users.actions')) ?></span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user->fullName) ?></td>
                    <td><?= e($user->email) ?></td>
                    <td><?= e(t('users.role_' . $user->role)) ?></td>
                    <td><span class="badge"><?= e($user->isActive ? t('users.active') : t('users.inactive')) ?></span></td>
                    <td><?= e(format_date($user->lastLoginAt, 'admin')) ?></td>
                    <td class="admin-table__actions">
                        <a class="button button--small" href="/admin/users/<?= e($user->id) ?>"><?= e(t('common.edit')) ?></a>
                        <form method="post" action="/admin/users/<?= e($user->id) ?>/<?= $user->isActive ? 'lock' : 'unlock' ?>" class="inline-form"
                              data-confirm="<?= e($user->isActive ? t('users.confirm_lock') : t('users.confirm_unlock')) ?>">
                            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button button--small<?= $user->isActive ? ' button--danger' : '' ?>">
                                <?= e($user->isActive ? t('users.lock') : t('users.unlock')) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
