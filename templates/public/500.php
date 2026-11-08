<?php
/** Trang lỗi máy chủ công khai. Không hiển thị chi tiết lỗi cho khách. */
?>
<!doctype html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('error.server')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#main-content"><?= e(t('common.skip_to_content')) ?></a>
    <main class="error-page" id="main-content">
        <p class="error-page__code">500</p>
        <h1><?= e(t('error.server')) ?></h1>
        <p><?= e(t('error.server_text')) ?></p>
        <p><a class="button button--primary" href="/"><?= e(t('error.back_home')) ?></a></p>
    </main>
</body>
</html>
