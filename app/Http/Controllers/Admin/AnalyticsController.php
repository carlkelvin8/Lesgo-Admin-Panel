<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\DailyMetric;
use App\Models\DailyReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RevenueAnalytics;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today           = now()->toDateString();
        $thirtyDaysAgo   = now()->subDays(30)->toDateString();
        $sevenDaysAgo    = now()->subDays(7)->toDateString();
        $thirtyDaysStart = now()->subDays(30)->startOfDay();

        // ── Revenue source ────────────────────────────────────────────────────
        [$revenue30, $srcName] = $this->revenueSource($thirtyDaysStart);

        $liveRevenue      = $this->revenueOf($revenue30, $srcName);
        $liveTransactions = (clone $revenue30)->count();
        $liveOrders       = Order::whereDate('created_at', '>=', $thirtyDaysAgo)->count();
        $liveNewUsers     = User::whereDate('created_at', '>=', $thirtyDaysAgo)->count();

        // ── Today metrics ─────────────────────────────────────────────────────
        $todayMetrics = DailyMetric::where('date', $today)->get()->groupBy('metric_type');

        if ($todayMetrics->isEmpty()) {
            $todayOrders = Order::whereDate('created_at', $today)->count();
            [$todayRev, $todaySrc] = $this->revenueSource(now()->startOfDay());
            $todayRevenue = $this->revenueOf($todayRev, $todaySrc);
            $todayUsers   = User::whereDate('created_at', $today)->count();

            $todayMetrics = collect([
                'operations' => collect([
                    (object) ['metric_key' => 'total_orders_today',  'metric_value' => $todayOrders],
                    (object) ['metric_key' => 'new_users_today',      'metric_value' => $todayUsers],
                    (object) ['metric_key' => 'total_revenue_today',  'metric_value' => $todayRevenue],
                ]),
            ]);
        }

        // ── Revenue by type ───────────────────────────────────────────────────
        $tableRevenue       = RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->get();
        $storedRevenueTotal = (float) $tableRevenue->sum('total_amount');

        if ($tableRevenue->isNotEmpty() && $storedRevenueTotal > 0) {
            $revenueByType    = $tableRevenue;
            $totalRevenue     = $storedRevenueTotal;
            $totalTransactions = (int) $tableRevenue->sum('total_transactions');
        } else {
            $revenueByType    = $this->revenueByTypeLive($revenue30, $srcName);
            $totalRevenue     = $liveRevenue;
            $totalTransactions = $liveTransactions;
        }

        // ── 30-day daily revenue trend ────────────────────────────────────────
        $tableTrend30      = DailyReport::where('report_date', '>=', $thirtyDaysAgo)
            ->select('report_date', 'total_revenue', 'total_orders')
            ->orderBy('report_date')
            ->get();
        $storedTrendTotal  = (float) $tableTrend30->sum('total_revenue');

        if ($tableTrend30->isNotEmpty() && $storedTrendTotal > 0) {
            $dailyRevenueTrend = $tableTrend30;
        } else {
            $dailyRevenueTrend = $this->dailyTrendLive(30);
        }

        // ── 7-day trend (kept for the summary panel) ──────────────────────────
        $tableTrend7 = DailyReport::where('report_date', '>=', $sevenDaysAgo)
            ->select('report_date', 'total_revenue', 'total_orders')
            ->orderBy('report_date')
            ->get();

        $recentReports = DailyReport::latest('report_date')->take(14)->get();

        // ── Order status distribution ─────────────────────────────────────────
        $orderStatusDist = Order::select('status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('status')
            ->get();

        // ── Orders by service (30d) ───────────────────────────────────────────
        $ordersByService = Order::whereDate('orders.created_at', '>=', $thirtyDaysAgo)
            ->leftJoin('services', 'services.id', '=', 'orders.service_id')
            ->select(
                DB::raw('COALESCE(services.name, "Unknown") as service_name'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('service_name')
            ->orderByDesc('total')
            ->get();

        // ── Payment method breakdown (30d) ────────────────────────────────────
        $paymentMethods = Order::whereDate('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // ── Hourly order heatmap (last 7 days) ────────────────────────────────
        $hourlyOrders = Order::where('created_at', '>=', now()->subDays(7)->startOfDay())
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Fill all 24 hours
        $hourlyData = collect(range(0, 23))->map(fn ($h) => [
            'hour'  => $h,
            'total' => $hourlyOrders->get($h)?->total ?? 0,
        ]);

        // ── New users per day (30d) ───────────────────────────────────────────
        $dailyNewUsers = User::where('created_at', '>=', $thirtyDaysStart)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Event stats ───────────────────────────────────────────────────────
        $eventStats = AnalyticsEvent::where('event_time', '>=', now()->subDays(7))
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // ── Summary stats ─────────────────────────────────────────────────────
        $activeDays = $dailyRevenueTrend->count();
        $stats = [
            'total_revenue'     => $totalRevenue,
            'total_orders'      => $liveOrders,
            'avg_daily_revenue' => $activeDays > 0 ? $totalRevenue / $activeDays : 0,
            'total_new_users'   => $liveNewUsers,
            'total_transactions' => $totalTransactions,
        ];

        return view('admin.analytics.index', compact(
            'stats', 'todayMetrics', 'recentReports', 'revenueByType',
            'dailyRevenueTrend', 'eventStats',
            'orderStatusDist', 'ordersByService', 'paymentMethods',
            'hourlyData', 'dailyNewUsers'
        ));
    }

    /**
     * Revenue source query.
     *
     * Uses completed orders as the primary source — covers both cash orders
     * (payment_status stays 'pending' after delivery) and online-paid orders.
     * Falls back to the payments table in legacy environments.
     *
     * @return array{0: Builder, 1: string}  [query, 'orders'|'payments']
     */
    private function revenueSource(?\Carbon\Carbon $from = null): array
    {
        if (Order::where('status', 'completed')->exists()) {
            return [
                Order::where('status', 'completed')->when($from, fn ($q) => $q->where('created_at', '>=', $from)),
                'orders',
            ];
        }

        return [
            Payment::where('status', 'paid')->when($from, fn ($q) => $q->where('paid_at', '>=', $from)),
            'payments',
        ];
    }

    private function revenueOf(Builder $query, string $source): float
    {
        $expression = $source === 'orders'
            ? DB::raw('COALESCE(actual_fare, estimated_fare, 0)')
            : DB::raw('amount');

        return (float) (clone $query)->sum($expression);
    }

    private function revenueByTypeLive(Builder $query, string $source): \Illuminate\Support\Collection
    {
        return collect([
            (object) [
                'revenue_type'      => 'gross',
                'total_amount'      => $this->revenueOf($query, $source),
                'total_transactions' => (clone $query)->count(),
            ],
        ]);
    }

    private function dailyTrendLive(int $days): \Illuminate\Support\Collection
    {
        $from = now()->subDays($days)->startOfDay();
        [$query, $source] = $this->revenueSource($from);

        $select = $source === 'orders'
            ? [
                DB::raw('DATE(created_at) as report_date'),
                DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as total_revenue'),
                DB::raw('COUNT(*) as total_orders'),
            ]
            : [
                DB::raw('DATE(COALESCE(paid_at, created_at)) as report_date'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('COUNT(*) as total_orders'),
            ];

        return (clone $query)
            ->select($select)
            ->groupBy('report_date')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => (object) [
                'report_date'   => Carbon::parse($row->report_date),
                'total_revenue' => $row->total_revenue,
                'total_orders'  => $row->total_orders,
            ]);
    }
}
