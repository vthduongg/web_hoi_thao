<?php
/**
 * Nhập thành viên ban từ tệp.
 *
 * @var array{id: int, name: string} $committee
 * @var list<string> $importErrors
 * @var string $csrfToken
 */
?>
<h1><?= e(t('import.title', ['committee' => $committee['name']])) ?></h1>

<?php if ($importErrors !== []): ?>
    <div class="flash flash--error" role="alert">
        <p><strong><?= e(t('import.failed')) ?></strong></p>
        <ul>
            <?php foreach ($importErrors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <p><?= e(t('import.help')) ?></p>
    <p>
        <a class="button button--small" href="/assets/templates/committee-import-template.xlsx" download><?= e(t('import.template_xlsx')) ?></a>
        <a class="button button--small" href="/assets/templates/committee-import-template.csv" download><?= e(t('import.template_csv')) ?></a>
    </p>
    <form method="post" action="/admin/committees/<?= e($committee['id']) ?>/import" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-field">
            <label for="file"><?= e(t('import.file')) ?></label>
            <input type="file" id="file" name="file" accept=".xlsx,.csv" required>
            <p class="form-field__hint"><?= e(t('import.limits')) ?></p>
        </div>
        <button type="submit" class="button button--primary"><?= e(t('import.button')) ?></button>
        <a class="button" href="/admin/committees/<?= e($committee['id']) ?>/members"><?= e(t('common.cancel')) ?></a>
    </form>
</div>
