<?php
/**
 * Bảng mốc thời gian (module important_dates).
 *
 * @var list<\App\Models\ImportantDate> $dates
 * @var ?\App\Models\ImportantDate $upcoming   mốc sắp tới gần nhất, được làm nổi bật
 */
?>
<?php if ($dates === []): ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php else: ?>
    <div class="table-scroll">
        <table class="data-table dates-table">
            <thead>
                <tr>
                    <th scope="col"><?= e(t('dates.milestone')) ?></th>
                    <th scope="col"><?= e(t('dates.date')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dates as $date): ?>
                    <?php $isUpcoming = $upcoming !== null && $upcoming->id === $date->id; ?>
                    <tr class="dates-table__row<?= $isUpcoming ? ' dates-table__row--upcoming' : '' ?>">
                        <td><?= e($date->label) ?><?php if ($isUpcoming): ?> <span class="badge badge--upcoming"><?= e(t('dates.upcoming')) ?></span><?php endif; ?></td>
                        <td><?= partial('public/partials/date-value', ['date' => $date]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
