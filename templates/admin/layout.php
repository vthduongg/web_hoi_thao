<?php
/**
 * Khung chung trang quản trị (tiếng Việt).
 *
 * @var string $content      HTML nội dung (đã render)
 * @var string $pageTitle    tiêu đề trang
 * @var ?array $currentUser  ['full_name', 'role', ...] người đang đăng nhập
 * @var ?\App\Models\Conference $editingConference hội thảo đang sửa
 * @var list<\App\Models\Conference> $allConferences
 * @var list<array{type: string, message: string}> $flashMessages
 * @var string $csrfToken
 * @var string $currentPath
 * @var list<string> $extraScripts  file JS riêng của trang (trong assets/js)
 */
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$menu = [
    ['/admin', t('nav.dashboard'), true],
    ['/admin/pages', t('nav.pages'), true],
    ['/admin/home', t('nav.home'), true],
    ['/admin/dates', t('nav.dates'), true],
    ['/admin/committees', t('nav.committees'), true],
    ['/admin/speakers', t('nav.speakers'), true],
    ['/admin/program', t('nav.program'), true],
    ['/admin/sponsors', t('nav.sponsors'), true],
    ['/admin/contacts', t('nav.contacts'), true],
    ['/admin/media', t('nav.media'), true],
    ['/admin/trash', t('nav.trash'), true],
    ['/admin/settings', t('nav.settings'), $isAdmin],
    ['/admin/menu', t('nav.menu'), $isAdmin],
    ['/admin/users', t('nav.users'), $isAdmin],
    ['/admin/conferences', t('nav.conferences'), $isAdmin],
    ['/admin/activity', t('nav.activity'), $isAdmin],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? '') ?> | <?= e(t('admin.title')) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script src="<?= e(asset('js/confirm-delete.js')) ?>" defer></script>
    <?php foreach ($extraScripts ?? [] as $script): ?>
        <script src="<?= e(asset('js/' . $script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
<div class="admin-shell">
    <nav class="admin-sidebar" aria-label="<?= e(t('admin.navigation')) ?>">
        <p class="admin-sidebar__title"><?= e(t('admin.title')) ?></p>
        <ul>
            <?php foreach ($menu as [$href, $label, $visible]): ?>
                <?php if ($visible): ?>
                    <li><a href="<?= e($href) ?>" <?= ($currentPath ?? '') === $href ? 'aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <?php if (($allConferences ?? []) !== []): ?>
                    <form method="post" action="/admin/switch-conference" class="inline-form">
                        <input type="hidden" name="_token" value="<?= e($csrfToken ?? '') ?>">
                        <label for="switch-conference"><?= e(t('admin.editing_conference')) ?></label>
                        <select id="switch-conference" name="conference_id">
                            <?php foreach ($allConferences as $item): ?>
                                <option value="<?= e($item->id) ?>" <?= $item->id === ($editingConference->id ?? null) ? 'selected' : '' ?>>
                                    <?= e($item->year) ?> — <?= e($item->name) ?><?= $item->isCurrent ? ' (' . e(t('admin.current')) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button--small"><?= e(t('admin.switch')) ?></button>
                    </form>
                <?php endif; ?>
            </div>
            <div>
                <span><?= e($currentUser['full_name'] ?? '') ?></span>
                · <a href="/admin/profile/password"><?= e(t('nav.change_password')) ?></a>
                · <form method="post" action="/admin/logout" class="inline-form">
                    <input type="hidden" name="_token" value="<?= e($csrfToken ?? '') ?>">
                    <button type="submit" class="button button--small"><?= e(t('nav.logout')) ?></button>
                </form>
            </div>
        </div>

        <?php foreach ($flashMessages ?? [] as $flash): ?>
            <div class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <main id="main-content">
            <?= $content /* HTML do template trang quản trị tạo, đã thoát */ ?>
        </main>
    </div>
</div>
</body>
</html>
