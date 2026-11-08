<?php
/**
 * Trang lỗi khu vực quản trị (tiếng Việt). Trang độc lập để luôn hiện được.
 *
 * @var int $status 403 | 404 | 405 | 419 | 500
 */
[$title, $text] = match ($status) {
    403 => [t('error.forbidden'), ''],
    404 => [t('error.not_found'), t('error.not_found_text')],
    405 => [t('error.method'), ''],
    419 => [t('error.csrf'), ''],
    default => [t('error.server'), t('error.server_text')],
};
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
    <main class="centered-page admin-card" id="main-content">
        <p><strong><?= e($status) ?></strong></p>
        <h1><?= e($title) ?></h1>
        <?php if ($text !== ''): ?>
            <p><?= e($text) ?></p>
        <?php endif; ?>
        <p><a class="button button--primary" href="/admin"><?= e(t('error.back')) ?></a></p>
    </main>
</body>
</html>
