<?php
/**
 * Bảng điều khiển (FR-021).
 *
 * @var string $userName
 * @var array{published: int, draft: int, hidden: int} $counts
 * @var list<\App\Models\ImportantDate> $upcomingDates
 * @var list<array{author: ?string, action: string, entity_type: ?string, entity_label: ?string, created_at: string}> $recentChanges
 */
?>
<h1><?= e(t('dashboard.greeting', ['name' => $userName])) ?></h1>

<div class="stat-grid">
    <div class="admin-card stat">
        <p class="stat__label"><?= e(t('dashboard.published_pages')) ?></p>
        <p class="stat__value"><strong data-count="published"><?= e($counts['published']) ?></strong></p>
    </div>
    <div class="admin-card stat">
        <p class="stat__label"><?= e(t('dashboard.draft_pages')) ?></p>
        <p class="stat__value"><strong data-count="draft"><?= e($counts['draft']) ?></strong></p>
    </div>
</div>

<div class="admin-card">
    <h2><?= e(t('dashboard.shortcuts')) ?></h2>
    <p>
        <a class="button" href="/admin/pages"><?= e(t('nav.pages')) ?></a>
        <a class="button" href="/admin/pages/new"><?= e(t('pages.create')) ?></a>
        <a class="button" href="/admin/dates"><?= e(t('nav.dates')) ?></a>
        <a class="button" href="/admin/home"><?= e(t('nav.home')) ?></a>
    </p>
</div>

<div class="admin-card">
    <h2><?= e(t('dashboard.upcoming_dates')) ?></h2>
    <?php if ($upcomingDates === []): ?>
        <p><?= e(t('common.empty')) ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($upcomingDates as $date): ?>
                <li><strong><?= e(format_date($date->dateStart, 'admin_date')) ?></strong> — <?= e($date->label) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2><?= e(t('dashboard.recent_changes')) ?></h2>
    <?php if ($recentChanges === []): ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentChanges as $change): ?>
                        <tr data-change-row>
                            <td><?= e(format_date($change['created_at'], 'admin')) ?></td>
                            <td><?= e($change['author'] ?? t('dashboard.system')) ?></td>
                            <td><?= e(t('action.' . $change['action'])) ?></td>
                            <td><?= e($change['entity_label']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
