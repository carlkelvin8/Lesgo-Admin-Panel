<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\RevenueAnalytics;
use App\Models\DailyMetric;
use App\Models\AnalyticsEvent;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $sevenDaysAgo = now()->subDays(7)->toDateString();

        $todayMetrics = DailyMetric::where('date', $today)
            ->get()
            ->groupBy('metric_type');

        $recentReports = DailyReport::latest('report_date')
            ->take(14)
            ->get();

        $revenueByType = RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->get();

        $totalRevenue = RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)->sum('amount');
        $totalTransactions = RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)->sum('transaction_count');
        $totalOrders = DailyReport::where('report_date', '>=', $thirtyDaysAgo)->sum('total_orders');
        $totalNewUsers = DailyReport::where('report_date', '>=', $thirtyDaysAgo)->sum('new_users');
        $avgDailyRevenue = DailyReport::where('report_date', '>=', $thirtyDaysAgo)->avg('total_revenue') ?? 0;

        $dailyRevenueTrend = DailyReport::where('report_date', '>=', $sevenDaysAgo)
            ->select('report_date', 'total_revenue', 'total_orders')
            ->orderBy('report_date')
            ->get();

        $eventStats = AnalyticsEvent::where('event_time', '>=', now()->subDays(7))
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $stats = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'avg_daily_revenue' => $avgDailyRevenue,
            'total_new_users' => $totalNewUsers,
            'total_transactions' => $totalTransactions,
        ];

        return view('admin.analytics.index', compact(
            'stats', 'todayMetrics', 'recentReports', 'revenueByType',
            'dailyRevenueTrend', 'eventStats'
        ));
    }
}
