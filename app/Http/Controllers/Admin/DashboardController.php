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

        // All seven queries run concurrently inside getAll(); the combined result
        // is also stored as a single cache entry so subsequent hits are instant.
        $data = $this->dashboardService->getAll($days);

        $stats                 = $data['stats'];
        $recent_orders         = $data['recentOrders'];
        $recent_users          = $data['recentUsers'];
        $dailyRevenue          = $data['dailyRevenue'];
        $orderStatusDistribution = $data['orderStatusDist'];
        $dailyUsers            = $data['dailyUsers'];
        $topPartners           = $data['topPartners'];

        return view('admin.dashboard', compact(
            'stats', 'recent_orders', 'recent_users',
            'dailyRevenue', 'orderStatusDistribution', 'dailyUsers', 'topPartners', 'days'
        ));
    }
}
