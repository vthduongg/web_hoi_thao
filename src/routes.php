<?php

declare(strict_types=1);

// Bảng route của toàn website.
// Quyền: null = công khai, 'E' = quản trị viên + biên tập viên, 'A' = chỉ quản trị viên.
// Route cụ thể phải khai báo TRƯỚC route chung (ví dụ /past-conferences trước /{slug}).

use App\Core\Router;

return static function (Router $r): void {
    // ----- Đăng nhập (không cần quyền) -----
    $r->get('/admin/login', 'Admin\\AuthController@showLogin');
    $r->post('/admin/login', 'Admin\\AuthController@login');
    $r->get('/admin/forgot-password', 'Admin\\AuthController@showForgot');
    $r->post('/admin/forgot-password', 'Admin\\AuthController@sendResetLink');
    $r->get('/admin/reset-password/{token}', 'Admin\\AuthController@showReset');
    $r->post('/admin/reset-password/{token}', 'Admin\\AuthController@reset');

    // ----- Quản trị: mọi người đã đăng nhập (E) -----
    $r->post('/admin/logout', 'Admin\\AuthController@logout', 'E');
    $r->get('/admin', 'Admin\\DashboardController@index', 'E');
    $r->get('/admin/profile/password', 'Admin\\ProfileController@showPassword', 'E');
    $r->post('/admin/profile/password', 'Admin\\ProfileController@updatePassword', 'E');

    // ----- Quản trị: chỉ quản trị viên (A) -----
    $r->get('/admin/users', 'Admin\\UserController@index', 'A');
    $r->get('/admin/users/new', 'Admin\\UserController@create', 'A');
    $r->post('/admin/users/new', 'Admin\\UserController@store', 'A');
    $r->get('/admin/users/{id}', 'Admin\\UserController@edit', 'A');
    $r->post('/admin/users/{id}', 'Admin\\UserController@update', 'A');
    $r->post('/admin/users/{id}/lock', 'Admin\\UserController@lock', 'A');
    $r->post('/admin/users/{id}/unlock', 'Admin\\UserController@unlock', 'A');

    // ----- Trang công khai (route chung /{slug} luôn để cuối) -----
    $r->get('/', 'Public\\HomeController@index');
    $r->get('/{slug}', 'Public\\PageController@show');
};
