<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;

/** Bảng điều khiển (bản khởi đầu; số liệu được bổ sung ở US2). */
final class DashboardController extends AdminController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard', [
            'pageTitle' => t('nav.dashboard'),
            'userName' => $this->currentUser()->fullName,
        ]);
    }
}
