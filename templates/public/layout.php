<?php
/**
 * Khung chung của mọi trang công khai.
 *
 * @var string  $content        HTML nội dung trang (đã render)
 * @var ?\App\Models\Conference $conference  hội thảo đang xem (biến dùng chung)
 * @var string  $pageTitle      tiêu đề trang (rỗng = trang chủ)
 * @var string  $metaDescription
 * @var string  $ogImage        đường dẫn ảnh chia sẻ
 * @var string  $canonicalUrl   đường dẫn đầy đủ của trang
 * @var ?string $faviconUrl
 * @var string  $bannerHtml     dải thông báo phía trên (lưu trữ / xem trước), có thể rỗng
 */
$conferenceName = $conference->name ?? '';
$fullTitle = ($pageTitle ?? '') !== '' ? $pageTitle . ' | ' . $conferenceName : $conferenceName;
?>
<!doctype html>
<html lang="<?= e($conference->publicLanguage ?? 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:url" content="<?= e($canonicalUrl ?? '') ?>">
    <?php if (($ogImage ?? '') !== ''): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <?php if (($faviconUrl ?? '') !== ''): ?>
        <link rel="icon" href="<?= e($faviconUrl) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
    <script src="<?= e(asset('js/menu-mobile.js')) ?>" defer></script>
    <script src="<?= e(asset('js/slider.js')) ?>" defer></script>
    <script src="<?= e(asset('js/copy-link.js')) ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content"><?= e(t('common.skip_to_content')) ?></a>
    <?= $bannerHtml ?? '' /* HTML do partial tạo, các giá trị bên trong đã được thoát */ ?>
    <?= partial('public/partials/header') ?>
    <main id="main-content">
        <?= $content /* HTML do template trang tạo, đã thoát/lọc */ ?>
    </main>
    <?= partial('public/partials/footer') ?>
</body>
</html>
