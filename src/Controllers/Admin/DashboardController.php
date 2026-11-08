<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\DashboardService;

/** Bảng điều khiển (FR-021). */
final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $conference = $this->editingConference();
        $dashboard = new DashboardService($this->services->db);

        return $this->render('admin/dashboard', [
            'pageTitle' => t('nav.dashboard'),
            'userName' => $this->currentUser()->fullName,
            'counts' => $conference === null ? ['published' => 0, 'draft' => 0, 'hidden' => 0] : $dashboard->pageCounts($conference->id),
            'upcomingDates' => $conference === null ? [] : $dashboard->upcomingDates($conference->id),
            'recentChanges' => $conference === null ? [] : $dashboard->recentChanges($conference->id),
        ]);
    }
}
