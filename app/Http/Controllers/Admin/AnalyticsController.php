<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\DailyMetric;
use App\Models\DailyReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RevenueAnalytics;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $sevenDaysAgo = now()->subDays(7)->toDateString();

        $paidPayments = fn (string $from) => Payment::where('status', 'paid')
            ->where(function ($query) use ($from) {
                $query->whereDate('paid_at', '>=', $from)
                    ->orWhere(fn ($fallback) => $fallback->whereNull('paid_at')->whereDate('created_at', '>=', $from));
            });

        $paid30 = $paidPayments($thirtyDaysAgo);
        $paid7 = $paidPayments($sevenDaysAgo);

        $liveRevenue = (clone $paid30)->sum('amount');
        $liveTransactions = (clone $paid30)->count();
        $liveOrders = Order::whereDate('created_at', '>=', $thirtyDaysAgo)->count();
        $liveNewUsers = User::whereDate('created_at', '>=', $thirtyDaysAgo)->count();

        $todayMetrics = DailyMetric::where('date', $today)
            ->get()
            ->groupBy('metric_type');

        $recentReports = DailyReport::latest('report_date')
            ->take(14)
            ->get();

        $tableRevenue = RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->get();

        if ($tableRevenue->isNotEmpty()) {
            $revenueByType = $tableRevenue;
            $totalRevenue = (float) $tableRevenue->sum('total_amount');
            $totalTransactions = (int) $tableRevenue->sum('total_transactions');
        } else {
            $revenueByType = collect([
                (object) ['revenue_type' => 'gross', 'total_amount' => $liveRevenue, 'total_transactions' => $liveTransactions],
            ]);
            $totalRevenue = $liveRevenue;
            $totalTransactions = $liveTransactions;
        }

        $tableTrend = DailyReport::where('report_date', '>=', $sevenDaysAgo)
            ->select('report_date', 'total_revenue', 'total_orders')
            ->orderBy('report_date')
            ->get();

        if ($tableTrend->isNotEmpty()) {
            $dailyRevenueTrend = $tableTrend;
        } else {
            $dailyRevenueTrend = (clone $paid7)
                ->select(
                    DB::raw('DATE(COALESCE(paid_at, created_at)) as report_date'),
                    DB::raw('SUM(amount) as total_revenue'),
                    DB::raw('COUNT(*) as total_orders')
                )
                ->groupBy(DB::raw('DATE(COALESCE(paid_at, created_at))'))
                ->orderBy('report_date')
                ->get()
                ->map(fn ($row) => (object) [
                    'report_date' => Carbon::parse($row->report_date),
                    'total_revenue' => $row->total_revenue,
                    'total_orders' => $row->total_orders,
                ]);
        }

        if ($todayMetrics->isEmpty()) {
            $todayOrders = Order::whereDate('created_at', $today)->count();
            $todayRevenue = (clone $paidPayments($today))->sum('amount');
            $todayUsers = User::whereDate('created_at', $today)->count();

            $todayMetrics = collect([
                'operations' => collect([
                    (object) ['metric_key' => 'total_orders_today', 'metric_value' => $todayOrders],
                    (object) ['metric_key' => 'new_users_today', 'metric_value' => $todayUsers],
                    (object) ['metric_key' => 'total_revenue_today', 'metric_value' => $todayRevenue],
                ]),
            ]);
        }

        $eventStats = AnalyticsEvent::where('event_time', '>=', now()->subDays(7))
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $activeDays = $dailyRevenueTrend->count();

        $stats = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $liveOrders,
            'avg_daily_revenue' => $activeDays > 0 ? $totalRevenue / $activeDays : 0,
            'total_new_users' => $liveNewUsers,
            'total_transactions' => $totalTransactions,
        ];

        return view('admin.analytics.index', compact(
            'stats', 'todayMetrics', 'recentReports', 'revenueByType',
            'dailyRevenueTrend', 'eventStats'
        ));
    }
}