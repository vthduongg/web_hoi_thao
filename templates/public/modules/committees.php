<?php
/**
 * Bảng thành viên các ban gắn với trang (module committees). STT đánh theo danh sách đang hiện.
 *
 * @var list<\App\Models\Committee> $committees
 */
?>
<?php if ($committees === []): ?>
    <p class="empty-note"><?= e(t('common.updating')) ?></p>
<?php endif; ?>
<?php foreach ($committees as $committee): ?>
    <section class="committee">
        <h2><?= e($committee->name) ?></h2>
        <?php if ($committee->members === []): ?>
            <p class="empty-note"><?= e(t('common.updating')) ?></p>
        <?php else: ?>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col"><?= e(t('committees.no')) ?></th>
                            <th scope="col"><?= e(t('committees.name')) ?></th>
                            <th scope="col"><?= e(t('committees.position')) ?></th>
                            <th scope="col"><?= e(t('committees.role')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($committee->members as $index => $member): ?>
                            <tr>
                                <td><?= e($index + 1) ?></td>
                                <td><?= e($member->displayName()) ?></td>
                                <td><?= e($member->positionAffiliation) ?></td>
                                <td><?= e($member->committeeRole) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
