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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyticsController extends Controller
{
    public function index()
    {
        try {
            return $this->renderIndex();
        } catch (Throwable $e) {
            Log::error('AnalyticsController@index failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => collect($e->getTrace())->take(8)->map(fn ($f) => ($f['file'] ?? '?').':'.($f['line'] ?? '?').' '.$f['function'])->implode("\n"),
            ]);
            throw $e;
        }
    }

    private function renderIndex()
    {
        $today           = now()->toDateString();
        $thirtyDaysAgo   = now()->subDays(30)->toDateString();
        $sevenDaysAgo    = now()->subDays(7)->toDateString();
        $thirtyDaysStart = now()->subDays(30)->startOfDay();

        // ── Revenue source ────────────────────────────────────────────────────
        [$revenue30, $srcName] = $this->revenueSource($thirtyDaysStart);

        $liveRevenue       = $this->revenueOf($revenue30, $srcName);
        $liveTransactions  = (clone $revenue30)->count();
        $liveOrders        = Order::whereDate('created_at', '>=', $thirtyDaysAgo)->count();
        $liveNewUsers      = User::whereDate('created_at', '>=', $thirtyDaysAgo)->count();

        // ── Today metrics ─────────────────────────────────────────────────────
        $todayMetrics = $this->safeGet(
            fn () => DailyMetric::where('date', $today)->get()->groupBy('metric_type'),
            collect()
        );

        if ($todayMetrics->isEmpty()) {
            $todayOrders  = Order::whereDate('created_at', $today)->count();
            [$todayRev, $todaySrc] = $this->revenueSource(now()->startOfDay());
            $todayRevenue = $this->revenueOf($todayRev, $todaySrc);
            $todayUsers   = User::whereDate('created_at', $today)->count();

            $todayMetrics = collect([
                'operations' => collect([
                    (object) ['metric_key' => 'total_orders_today', 'metric_value' => $todayOrders],
                    (object) ['metric_key' => 'new_users_today',    'metric_value' => $todayUsers],
                    (object) ['metric_key' => 'total_revenue_today','metric_value' => $todayRevenue],
                ]),
            ]);
        }

        // ── Revenue by type ───────────────────────────────────────────────────
        // Guarded: revenue_analytics may not exist on every deploy.
        $tableRevenue = $this->safeGet(
            fn () => RevenueAnalytics::where('date', '>=', $thirtyDaysAgo)
                ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
                ->groupBy('revenue_type')
                ->get(),
            collect()
        );
        $storedRevenueTotal = (float) $tableRevenue->sum('total_amount');

        if ($tableRevenue->isNotEmpty() && $storedRevenueTotal > 0) {
            $revenueByType     = $tableRevenue;
            $totalRevenue      = $storedRevenueTotal;
            $totalTransactions = (int) $tableRevenue->sum('total_transactions');
        } else {
            $revenueByType     = $this->revenueByTypeLive($revenue30, $srcName);
            $totalRevenue      = $liveRevenue;
            $totalTransactions = $liveTransactions;
        }

        // ── 30-day daily revenue trend ────────────────────────────────────────
        // Guarded: daily_reports may be empty / missing on some deploys.
        $tableTrend = $this->safeGet(
            fn () => DailyReport::where('report_date', '>=', $thirtyDaysAgo)
                ->select('report_date', 'total_revenue', 'total_orders')
                ->orderBy('report_date')
                ->get(),
            collect()
        );

        if ($tableTrend->isNotEmpty() && (float) $tableTrend->sum('total_revenue') > 0) {
            $dailyRevenueTrend = $tableTrend;
        } else {
            $dailyRevenueTrend = $this->dailyTrendLive(30);
        }

        $recentReports = $this->safeGet(
            fn () => DailyReport::latest('report_date')->take(14)->get(),
            collect()
        );

        // ── Order status distribution (30d) ───────────────────────────────────
        $orderStatusDist = Order::select('status', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('status')
            ->get();

        // ── Orders by service (30d) ───────────────────────────────────────────
        // NOTE: group by the raw COALESCE expression (not the alias) so this
        // works on both MySQL and Postgres (laravel.cloud).
        $ordersByService = Order::whereDate('orders.created_at', '>=', $thirtyDaysAgo)
            ->leftJoin('services', 'services.id', '=', 'orders.service_id')
            ->select(
                DB::raw("COALESCE(services.name, 'Unknown') as service_name"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy(DB::raw("COALESCE(services.name, 'Unknown')"))
            ->orderByDesc('total')
            ->get();

        // ── Payment method breakdown (30d) ────────────────────────────────────
        $paymentMethods = Order::whereDate('created_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // ── Hourly order heatmap (last 7 days) — pre-built as simple array ────
        // DB-agnostic: HOUR() is MySQL-only and fatals on Postgres
        // (laravel.cloud), so aggregate in PHP instead.
        $hourCounts = array_fill(0, 24, 0);
        try {
            Order::where('created_at', '>=', now()->subDays(7)->startOfDay())
                ->pluck('created_at')
                ->each(function ($ts) use (&$hourCounts) {
                    if ($ts === null) {
                        return;
                    }
                    $hourCounts[(int) Carbon::parse($ts)->format('H')]++;
                });
        } catch (Throwable $e) {
            Log::warning('Analytics hourly heatmap fallback', ['message' => $e->getMessage()]);
        }

        $hourlyLabels = [];
        $hourlyValues = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyLabels[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $hourlyValues[] = (int) ($hourCounts[$h] ?? 0);
        }

        // ── New users per day (30d) — pre-built as simple arrays ─────────────
        // DB-agnostic: DATE() is MySQL-only, so aggregate in PHP.
        $dailyUserLabels = [];
        $dailyUserValues = [];
        try {
            $grouped = [];
            User::where('created_at', '>=', $thirtyDaysStart)
                ->pluck('created_at')
                ->each(function ($ts) use (&$grouped) {
                    if ($ts === null) {
                        return;
                    }
                    $key = Carbon::parse($ts)->toDateString();
                    $grouped[$key] = ($grouped[$key] ?? 0) + 1;
                });
            ksort($grouped);

            foreach ($grouped as $date => $total) {
                $dailyUserLabels[] = Carbon::parse($date)->format('M d');
                $dailyUserValues[] = (int) $total;
            }
        } catch (Throwable $e) {
            Log::warning('Analytics daily users fallback', ['message' => $e->getMessage()]);
        }

        // ── Revenue trend — pre-built as simple arrays ────────────────────────
        $trendLabels  = $dailyRevenueTrend->map(fn ($r) => Carbon::parse($r->report_date)->format('M d'))->values()->toArray();
        $trendRevenue = $dailyRevenueTrend->map(fn ($r) => round((float) $r->total_revenue, 2))->values()->toArray();
        $trendOrders  = $dailyRevenueTrend->map(fn ($r) => (int) $r->total_orders)->values()->toArray();

        // ── Event stats ───────────────────────────────────────────────────────
        // Guarded: analytics_events may not exist on every deploy.
        $eventStats = $this->safeGet(
            fn () => AnalyticsEvent::where('event_time', '>=', now()->subDays(7))
                ->select('event_type', DB::raw('COUNT(*) as count'))
                ->groupBy('event_type')
                ->orderByDesc('count')
                ->take(5)
                ->get(),
            collect()
        );

        // ── Summary stats ─────────────────────────────────────────────────────
        $activeDays = $dailyRevenueTrend->count();
        $stats = [
            'total_revenue'      => $totalRevenue,
            'total_orders'       => $liveOrders,
            'avg_daily_revenue'  => $activeDays > 0 ? $totalRevenue / $activeDays : 0,
            'total_new_users'    => $liveNewUsers,
            'total_transactions' => $totalTransactions,
        ];

        return view('admin.analytics.index', compact(
            'stats', 'todayMetrics', 'recentReports', 'revenueByType',
            'dailyRevenueTrend', 'eventStats',
            'orderStatusDist', 'ordersByService', 'paymentMethods',
            'hourlyLabels', 'hourlyValues',
            'dailyUserLabels', 'dailyUserValues',
            'trendLabels', 'trendRevenue', 'trendOrders'
        ));
    } // end renderIndex()

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
                'revenue_type'       => 'gross',
                'total_amount'       => $this->revenueOf($query, $source),
                'total_transactions' => (clone $query)->count(),
            ],
        ]);
    }

    private function dailyTrendLive(int $days): \Illuminate\Support\Collection
    {
        // DB-agnostic: DATE()/DATE_TRUNC differ per driver (MySQL vs Postgres
        // on laravel.cloud), so aggregate in PHP. Fills every day in the
        // window so the chart never gaps.
        $from = now()->subDays($days)->startOfDay();
        [$query, $source] = $this->revenueSource($from);

        try {
            if ($source === 'orders') {
                $rows = (clone $query)
                    ->select('created_at', 'actual_fare', 'estimated_fare')
                    ->get();
            } else {
                $rows = (clone $query)
                    ->select('paid_at', 'created_at', 'amount')
                    ->get();
            }
        } catch (Throwable $e) {
            Log::warning('Analytics dailyTrendLive fallback', ['message' => $e->getMessage()]);

            return collect();
        }

        $grouped = [];
        foreach ($rows as $row) {
            if ($source === 'orders') {
                $date = $row->created_at ? Carbon::parse($row->created_at)->toDateString() : null;
                $fare = (float) ($row->actual_fare ?? $row->estimated_fare ?? 0);
            } else {
                $ts = $row->paid_at ?? $row->created_at;
                $date = $ts ? Carbon::parse($ts)->toDateString() : null;
                $fare = (float) ($row->amount ?? 0);
            }
            if ($date === null) {
                continue;
            }
            $grouped[$date] ??= ['total_revenue' => 0.0, 'total_orders' => 0];
            $grouped[$date]['total_revenue'] += $fare;
            $grouped[$date]['total_orders']++;
        }

        $out = collect();
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $out->push((object) [
                'report_date'   => Carbon::parse($date),
                'total_revenue' => round($grouped[$date]['total_revenue'] ?? 0, 2),
                'total_orders'  => (int) ($grouped[$date]['total_orders'] ?? 0),
            ]);
        }

        return $out;
    }

    /**
     * Run a query against an optional analytics table without ever 500ing
     * when the table/migration is missing on an environment.
     */
    private function safeGet(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            Log::warning('Analytics optional query fallback', ['message' => $e->getMessage()]);

            return $fallback ?? collect();
        }
    }
}
