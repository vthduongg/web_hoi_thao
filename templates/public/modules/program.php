<?php
/**
 * Chương trình hội thảo (module program): bảng theo ngày → phiên → mục; nếu chưa có thì ảnh/PDF thay thế.
 *
 * @var list<\App\Models\ProgramDay> $days
 * @var ?\App\Models\MediaFile $programFile
 */
?>
<?php if ($days !== []): ?>
    <?php foreach ($days as $day): ?>
        <section class="program-day">
            <h2><?= e(format_date($day->dayDate)) ?><?= ($day->title ?? '') !== '' ? ' — ' . e($day->title) : '' ?></h2>
            <?php foreach ($day->sessions as $session): ?>
                <h3><?= e($session->title) ?></h3>
                <div class="table-scroll">
                    <table class="data-table program-table">
                        <thead>
                            <tr>
                                <th scope="col"><?= e(t('program.time')) ?></th>
                                <th scope="col"><?= e(t('program.content')) ?></th>
                                <th scope="col"><?= e(t('program.presenter')) ?></th>
                                <th scope="col"><?= e(t('program.room')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($session->items as $item): ?>
                                <tr>
                                    <td><?= e($item->startTime) ?> – <?= e($item->endTime) ?></td>
                                    <td><?= e($item->content) ?></td>
                                    <td><?= e($item->presenter) ?></td>
                                    <td><?= e(($item->room ?? '') !== '' ? $item->room : $session->room) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
<?php elseif ($programFile !== null && $programFile->isImage()): ?>
    <p><img src="<?= e(upload_url($programFile->storedPath)) ?>" alt="<?= e(t('program.image_alt')) ?>"></p>
<?php elseif ($programFile !== null): ?>
    <p><a class="button button--primary" href="<?= e(upload_url($programFile->storedPath)) ?>" target="_blank" rel="noopener"><?= e(t('program.download')) ?> (<?= e($programFile->originalName) ?>)</a></p>
<?php else: ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php endif; ?>
