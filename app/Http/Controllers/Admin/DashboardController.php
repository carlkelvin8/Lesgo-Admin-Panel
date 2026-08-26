<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index()
    {
        $days = (int) request('days', 7);
        $days = max(1, min(90, $days));

        $stats = $this->dashboardService->getStats();
        $recent_orders = $this->dashboardService->getRecentOrders();
        $recent_users = $this->dashboardService->getRecentUsers();
        $dailyRevenue = $this->dashboardService->getDailyRevenue($days);
        $orderStatusDistribution = $this->dashboardService->getOrderStatusDistribution();
        $dailyUsers = $this->dashboardService->getDailyUsers($days);
        $topPartners = $this->dashboardService->getTopPartners($days);

        return view('admin.dashboard', compact(
            'stats', 'recent_orders', 'recent_users',
            'dailyRevenue', 'orderStatusDistribution', 'dailyUsers', 'topPartners', 'days'
        ));
    }
}
