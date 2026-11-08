<?php
/**
 * Hiển thị ngày của một mốc: "To be announced", khoảng ngày, hoặc ngày gia hạn (ngày cũ gạch ngang).
 *
 * @var \App\Models\ImportantDate $date
 */
?>
<?php if ($date->isTba()): ?>
    <em><?= e(t('dates.tba')) ?></em>
<?php else: ?>
    <?php if ($date->isExtended()): ?><del><?= e(format_date($date->originalDate)) ?></del> <?php endif; ?>
    <span class="date-value"><?= e(format_date($date->dateStart)) ?><?php if ($date->dateEnd !== null && $date->dateEnd !== $date->dateStart): ?> – <?= e(format_date($date->dateEnd)) ?><?php endif; ?></span>
    <?php if ($date->isExtended()): ?> <span class="badge badge--extended"><?= e(t('dates.extended')) ?></span><?php endif; ?>
<?php endif; ?>
