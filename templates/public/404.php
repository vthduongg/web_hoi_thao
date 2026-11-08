<?php
/** Trang 404 công khai (ngôn ngữ công khai, lang/en.php). Trang độc lập để vẫn hiện được khi thiếu dữ liệu. */
?>
<!doctype html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('error.not_found')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
</head>
<body>
    <main class="error-page" id="main-content">
        <p class="error-page__code">404</p>
        <h1><?= e(t('error.not_found')) ?></h1>
        <p><?= e(t('error.not_found_text')) ?></p>
        <p><a class="button button--primary" href="/"><?= e(t('error.back_home')) ?></a></p>
    </main>
</body>
</html>
