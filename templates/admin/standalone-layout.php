<?php
/**
 * Khung trang quản trị không có thanh bên: đăng nhập, quên/đặt lại mật khẩu.
 *
 * @var string $content
 * @var string $pageTitle
 * @var list<array{type: string, message: string}> $flashMessages
 */
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? t('admin.title')) ?> | <?= e(t('admin.title')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body>
    <main class="centered-page" id="main-content">
        <div class="admin-card">
            <?php foreach ($flashMessages ?? [] as $flash): ?>
                <div class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
            <?= $content /* HTML do template tạo, đã thoát */ ?>
        </div>
    </main>
</body>
</html>
