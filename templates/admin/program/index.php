<?php
/**
 * Tổng quan chương trình: ngày → phiên → mục, và tệp chương trình thay thế.
 *
 * @var list<\App\Models\ProgramDay> $days
 * @var ?\App\Models\MediaFile $programFile
 * @var string $csrfToken
 */
?>
<h1><?= e(t('program.title')) ?></h1>
<p class="form-field__hint"><?= e(t('program.help')) ?></p>

<div class="admin-card">
    <h2><?= e(t('program.file')) ?></h2>
    <p class="form-field__hint"><?= e(t('program.file_hint')) ?></p>
    <form method="post" action="/admin/program/file">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <?= partial('admin/partials/media-field', ['name' => 'program_file_media_id', 'label' => t('program.file'), 'media' => $programFile, 'kind' => '']) ?>
        <button type="submit" class="button button--primary"><?= e(t('common.save')) ?></button>
    </form>
</div>

<p><a class="button button--primary" href="/admin/program/days/new"><?= e(t('program.day_create')) ?></a>
   <a class="button" href="/admin/program/days"><?= e(t('program.days_order')) ?></a></p>

<?php if ($days === []): ?>
    <p><?= e(t('common.empty')) ?></p>
<?php endif; ?>

<?php foreach ($days as $day): ?>
    <section class="admin-card program-day">
        <h2>
            <?= e(format_date($day->dayDate, 'admin_date')) ?><?= ($day->title ?? '') !== '' ? ' — ' . e($day->title) : '' ?>
            <a class="button button--small" href="/admin/program/days/<?= e($day->id) ?>"><?= e(t('common.edit')) ?></a>
            <a class="button button--small" href="/admin/program/days/<?= e($day->id) ?>/sessions/new"><?= e(t('program.session_create')) ?></a>
            <a class="button button--small" href="/admin/program/days/<?= e($day->id) ?>/sessions"><?= e(t('program.sessions_order')) ?></a>
        </h2>
        <?php foreach ($day->sessions as $session): ?>
            <h3>
                <?= e($session->title) ?><?= ($session->room ?? '') !== '' ? ' (' . e($session->room) . ')' : '' ?>
                <a class="button button--small" href="/admin/program/days/<?= e($day->id) ?>/sessions/<?= e($session->id) ?>"><?= e(t('common.edit')) ?></a>
                <a class="button button--small" href="/admin/program/sessions/<?= e($session->id) ?>/items/new"><?= e(t('program.item_create')) ?></a>
                <a class="button button--small" href="/admin/program/sessions/<?= e($session->id) ?>/items"><?= e(t('program.items_manage')) ?></a>
            </h3>
            <?php if ($session->items === []): ?>
                <p class="form-field__hint"><?= e(t('common.empty')) ?></p>
            <?php else: ?>
                <ul>
                    <?php foreach ($session->items as $item): ?>
                        <li><strong><?= e($item->startTime) ?>–<?= e($item->endTime) ?></strong> <?= e($item->content) ?><?= ($item->presenter ?? '') !== '' ? ' — ' . e($item->presenter) : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>
