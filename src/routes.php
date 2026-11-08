<?php

declare(strict_types=1);

// Bảng route của toàn website, chia theo nhóm chức năng.
// Quyền: null = công khai, 'E' = quản trị viên + biên tập viên, 'A' = chỉ quản trị viên.
// Route cụ thể phải khai báo TRƯỚC route chung (ví dụ /past-conferences trước /{slug}),
// vì vậy nhóm trang công khai luôn được đăng ký cuối cùng.

use App\Core\Router;

/** Đăng nhập, quên mật khẩu (không cần quyền), đăng xuất, đổi mật khẩu. */
function registerAuthRoutes(Router $r): void
{
    $r->get('/admin/login', 'Admin\\AuthController@showLogin');
    $r->post('/admin/login', 'Admin\\AuthController@login');
    $r->get('/admin/forgot-password', 'Admin\\AuthController@showForgot');
    $r->post('/admin/forgot-password', 'Admin\\AuthController@sendResetLink');
    $r->get('/admin/reset-password/{token}', 'Admin\\AuthController@showReset');
    $r->post('/admin/reset-password/{token}', 'Admin\\AuthController@reset');
    $r->post('/admin/logout', 'Admin\\AuthController@logout', 'E');
    $r->get('/admin/profile/password', 'Admin\\ProfileController@showPassword', 'E');
    $r->post('/admin/profile/password', 'Admin\\ProfileController@updatePassword', 'E');
}

/** Bảng điều khiển, trang nội dung, thư viện tệp (quản trị viên + biên tập viên). */
function registerContentRoutes(Router $r): void
{
    $r->get('/admin', 'Admin\\DashboardController@index', 'E');
    $r->post('/admin/switch-conference', 'Admin\\ConferenceController@switch', 'E');

    $r->get('/admin/pages', 'Admin\\PageController@index', 'E');
    $r->get('/admin/pages/new', 'Admin\\PageController@create', 'E');
    $r->post('/admin/pages/new', 'Admin\\PageController@store', 'E');
    $r->get('/admin/pages/{id}', 'Admin\\PageController@edit', 'E');
    $r->post('/admin/pages/{id}', 'Admin\\PageController@update', 'E');
    $r->get('/admin/pages/{id}/preview', 'Admin\\PageController@preview', 'E');
    $r->post('/admin/pages/{id}/publish', 'Admin\\PageController@publish', 'E');
    $r->post('/admin/pages/{id}/hide', 'Admin\\PageController@hide', 'E');
    $r->post('/admin/pages/{id}/draft', 'Admin\\PageController@draft', 'E');
    $r->get('/admin/pages/{id}/revisions', 'Admin\\PageController@revisions', 'E');
    $r->post('/admin/pages/{id}/revisions/{revisionNo}/restore', 'Admin\\PageController@restore', 'E');
    $r->post('/admin/pages/{id}/delete', 'Admin\\PageController@delete', 'E');

    $r->get('/admin/media', 'Admin\\MediaController@index', 'E');
    $r->get('/admin/media/picker', 'Admin\\MediaController@picker', 'E');
    $r->post('/admin/media/upload', 'Admin\\MediaController@upload', 'E');
    $r->get('/admin/media/{id}', 'Admin\\MediaController@edit', 'E');
    $r->post('/admin/media/{id}', 'Admin\\MediaController@update', 'E');
    $r->post('/admin/media/{id}/delete', 'Admin\\MediaController@delete', 'E');

    // Thùng rác: khôi phục (mọi người), xoá vĩnh viễn (chỉ quản trị viên)
    $r->get('/admin/trash', 'Admin\\TrashController@index', 'E');
    $r->post('/admin/trash/{type}/{id}/restore', 'Admin\\TrashController@restore', 'E');
    $r->post('/admin/trash/{type}/{id}/purge', 'Admin\\TrashController@purge', 'A');
}

/**
 * Bộ route chuẩn cho một nhóm dữ liệu dùng CrudController:
 * danh sách, thêm, sửa, Hiện/Ẩn, sắp xếp, xoá. $prefix có thể chứa tham số, ví dụ "/admin/committees/{committeeId}/members".
 */
function registerCrudRoutes(Router $r, string $prefix, string $controller, string $permission = 'E'): void
{
    $r->get($prefix, "$controller@index", $permission);
    $r->get("$prefix/new", "$controller@create", $permission);
    $r->post("$prefix/new", "$controller@store", $permission);
    $r->post("$prefix/reorder", "$controller@reorder", $permission);
    $r->get("$prefix/{id}", "$controller@edit", $permission);
    $r->post("$prefix/{id}", "$controller@update", $permission);
    $r->post("$prefix/{id}/toggle-visibility", "$controller@toggleVisibility", $permission);
    $r->post("$prefix/{id}/delete", "$controller@delete", $permission);
}

/** Dữ liệu có cấu trúc: mốc thời gian, ban, diễn giả, chương trình, nhà tài trợ, người liên hệ (US3). */
function registerStructuredDataRoutes(Router $r): void
{
    registerCrudRoutes($r, '/admin/dates', 'Admin\\DateController');
    registerCrudRoutes($r, '/admin/committees', 'Admin\\CommitteeController');
    registerCrudRoutes($r, '/admin/committees/{committeeId}/members', 'Admin\\CommitteeMemberController');
    $r->get('/admin/committees/{committeeId}/import', 'Admin\\CommitteeImportController@show', 'E');
    $r->post('/admin/committees/{committeeId}/import', 'Admin\\CommitteeImportController@import', 'E');
    registerCrudRoutes($r, '/admin/speakers', 'Admin\\SpeakerController');

    $r->get('/admin/program', 'Admin\\ProgramController@index', 'E');
    $r->post('/admin/program/file', 'Admin\\ProgramController@saveFile', 'E');
    registerCrudRoutes($r, '/admin/program/days', 'Admin\\ProgramDayController');
    registerCrudRoutes($r, '/admin/program/days/{dayId}/sessions', 'Admin\\ProgramSessionController');
    registerCrudRoutes($r, '/admin/program/sessions/{sessionId}/items', 'Admin\\ProgramItemController');

    registerCrudRoutes($r, '/admin/sponsor-tiers', 'Admin\\SponsorTierController');
    registerCrudRoutes($r, '/admin/sponsors', 'Admin\\SponsorController');
    registerCrudRoutes($r, '/admin/contacts', 'Admin\\ContactController');

    // Trang chủ: đoạn giới thiệu, slider, khối nổi bật (US6)
    $r->get('/admin/home', 'Admin\\HomeController@index', 'E');
    $r->post('/admin/home/intro', 'Admin\\HomeController@saveIntro', 'E');
    registerCrudRoutes($r, '/admin/home/slides', 'Admin\\SlideController');
    registerCrudRoutes($r, '/admin/home/highlights', 'Admin\\HighlightController');
}

/** Chức năng chỉ dành cho quản trị viên. */
function registerAdminOnlyRoutes(Router $r): void
{
    $r->get('/admin/users', 'Admin\\UserController@index', 'A');
    $r->get('/admin/users/new', 'Admin\\UserController@create', 'A');
    $r->post('/admin/users/new', 'Admin\\UserController@store', 'A');
    $r->get('/admin/users/{id}', 'Admin\\UserController@edit', 'A');
    $r->post('/admin/users/{id}', 'Admin\\UserController@update', 'A');
    $r->post('/admin/users/{id}/lock', 'Admin\\UserController@lock', 'A');
    $r->post('/admin/users/{id}/unlock', 'Admin\\UserController@unlock', 'A');

    // Các kỳ hội thảo: sao chép sang năm mới, đặt hiện hành (US8)
    $r->get('/admin/conferences', 'Admin\\ConferenceController@index', 'A');
    $r->post('/admin/conferences/{id}/copy', 'Admin\\ConferenceController@copy', 'A');
    $r->post('/admin/conferences/{id}/make-current', 'Admin\\ConferenceController@makeCurrent', 'A');

    // Nhật ký: chỉ đọc, không có route sửa/xoá
    $r->get('/admin/activity', 'Admin\\ActivityController@index', 'A');

    // Thông tin chung và menu (US6)
    $r->get('/admin/settings', 'Admin\\SettingsController@edit', 'A');
    $r->post('/admin/settings', 'Admin\\SettingsController@update', 'A');
    registerCrudRoutes($r, '/admin/menu', 'Admin\\MenuController', 'A');
}

/** Trang công khai — đăng ký CUỐI CÙNG vì /{slug} khớp mọi đường dẫn một cấp. */
function registerPublicRoutes(Router $r): void
{
    $r->get('/', 'Public\\HomeController@index');
    $r->get('/sitemap.xml', 'Public\\SitemapController@sitemap');
    $r->get('/robots.txt', 'Public\\SitemapController@robots');
    $r->get('/past-conferences', 'Public\\ArchiveController@index');
    $r->get('/{year}', 'Public\\ArchiveController@home');
    $r->get('/{year}/{slug}', 'Public\\ArchiveController@page');
    $r->get('/{slug}', 'Public\\PageController@show');
}

return static function (Router $r): void {
    registerAuthRoutes($r);
    registerContentRoutes($r);
    registerStructuredDataRoutes($r);
    registerAdminOnlyRoutes($r);
    registerPublicRoutes($r);
};
