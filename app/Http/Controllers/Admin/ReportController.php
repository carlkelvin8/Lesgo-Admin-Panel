<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMetric;
use App\Models\DailyReport;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RevenueAnalytics;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = DailyReport::query();

        if ($request->filled('date_from')) {
            $query->where('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('report_date', '<=', $request->date_to);
        }

        $reports = $query->orderByDesc('report_date')->paginate(20)->withQueryString();

        $since = now()->subDays(30)->startOfDay();

        [$revenueQuery, $revenueSource] = $this->revenueSource($since);

        $liveSummary = [
            'orders_30d' => Order::where('created_at', '>=', $since)->count(),
            'revenue_30d' => $this->revenueOf($revenueQuery, $revenueSource),
            'transactions_30d' => (clone $revenueQuery)->count(),
            'new_users_30d' => User::where('created_at', '>=', $since)->count(),
            'new_drivers_30d' => DriverProfile::where('created_at', '>=', $since)->count(),
        ];

        return view('admin.reports.index', compact('reports', 'liveSummary'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'report_date' => 'required|date|before_or_equal:today',
        ]);

        $date = $validated['report_date'];
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end]);
        [$revenueQuery, $revenueSource] = $this->revenueSource($start, $end);

        $values = [
            'total_orders' => (clone $orders)->count(),
            'completed_orders' => (clone $orders)->where('status', 'completed')->count(),
            'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
            'new_users' => User::whereBetween('created_at', [$start, $end])->count(),
            'new_drivers' => DriverProfile::whereBetween('created_at', [$start, $end])->count(),
            'total_revenue' => $this->revenueOf($revenueQuery, $revenueSource),
            'avg_fare' => (clone $orders)->where('status', 'completed')->avg('actual_fare') ?? 0,
            'total_distance_km' => (int) round(((clone $orders)->sum('actual_distance_m') ?? 0) / 1000),
        ];

        DB::transaction(function () use ($date, $values, $revenueQuery) {
            DailyReport::updateOrCreate(
                ['report_date' => $date],
                [...$values, 'meta' => ['generated_by' => auth()->id(), 'generated_at' => now()->toIso8601String()]]
            );

            foreach ([
                'total_orders' => $values['total_orders'],
                'completed_orders' => $values['completed_orders'],
                'cancelled_orders' => $values['cancelled_orders'],
                'new_users' => $values['new_users'],
                'new_drivers' => $values['new_drivers'],
                'total_revenue' => $values['total_revenue'],
            ] as $key => $value) {
                DailyMetric::updateOrCreate(
                    ['date' => $date, 'metric_type' => str_contains($key, 'revenue') ? 'revenue' : 'operations', 'metric_category' => 'daily', 'metric_key' => $key],
                    ['metric_value' => $value]
                );
            }

            $transactionCount = (clone $revenueQuery)->count();
            RevenueAnalytics::updateOrCreate(
                ['date' => $date, 'revenue_type' => 'gross', 'revenue_source' => 'orders', 'service_id' => null, 'partner_id' => null],
                [
                    'amount' => $values['total_revenue'],
                    'currency' => 'PHP',
                    'transaction_count' => $transactionCount,
                    'average_transaction_value' => $transactionCount > 0 ? $values['total_revenue'] / $transactionCount : 0,
                ]
            );
        });

        return redirect()->route('admin.reports.daily', $date)
            ->with('success', "Report for {$date} generated successfully.");
    }

    public function daily(Request $request, string $date)
    {
        $start = Carbon::parse($date)->startOfDay();
        $end = $start->copy()->endOfDay();

        // Always fetch the report's numbers live from orders/users/drivers so
        // the page (and its PDF) reflects real data, not stale stored values.
        $orders = Order::whereBetween('created_at', [$start, $end]);
        [$revenueQuery, $revenueSource] = $this->revenueSource($start, $end);

        $totalOrders = (clone $orders)->count();
        $completedOrders = (clone $orders)->where('status', 'completed')->count();
        $cancelledOrders = (clone $orders)->where('status', 'cancelled')->count();
        $newUsers = User::whereBetween('created_at', [$start, $end])->count();
        $newDrivers = DriverProfile::whereBetween('created_at', [$start, $end])->count();
        $totalRevenue = $this->revenueOf($revenueQuery, $revenueSource);
        $avgFare = (clone $orders)->where('status', 'completed')->avg('actual_fare') ?? 0;
        $totalDistanceKm = (int) round(((clone $orders)->sum('actual_distance_m') ?? 0) / 1000);

        $stored = DailyReport::where('report_date', $date)->first();

        $report = (object) [
            'report_date' => Carbon::parse($date),
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'new_users' => $newUsers,
            'new_drivers' => $newDrivers,
            'total_revenue' => $totalRevenue,
            'avg_fare' => (float) $avgFare,
            'total_distance_km' => $totalDistanceKm,
            'meta' => $stored?->meta ?? [],
        ];

        $metrics = DailyMetric::where('date', $date)
            ->get()
            ->groupBy('metric_type');

        $revenueDetails = RevenueAnalytics::where('date', $date)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->get();

        if ($revenueDetails->isEmpty() || (float) $revenueDetails->sum('total_amount') == 0) {
            $revenueDetails = collect([
                (object) [
                    'revenue_type' => 'gross',
                    'total_amount' => $totalRevenue,
                    'total_transactions' => (clone $revenueQuery)->count(),
                ],
            ]);
        }

        if ($request->boolean('print')) {
            return view('admin.reports.print', [
                'report' => $report,
                'completionRate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0,
                'cancelRate' => $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 1) : 0,
                'otherOrders' => max(0, $totalOrders - $completedOrders - $cancelledOrders),
                'revenueDetails' => $revenueDetails,
            ]);
        }

        return view('admin.reports.daily', compact('report', 'metrics', 'revenueDetails'));
    }

    public function revenue(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = RevenueAnalytics::query();

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        } else {
            $query->where('date', '>=', now()->subDays(30)->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if (! (clone $query)->exists()) {
            return $this->liveRevenue($request);
        }

        $summary = (clone $query)
            ->select(
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('SUM(transaction_count) as total_transactions'),
                DB::raw('AVG(average_transaction_value) as avg_transaction'),
                DB::raw('COUNT(DISTINCT date) as days_with_data')
            )
            ->first();

        // A stored analytics row that is all zeros is not real data (e.g. it was
        // written when the empty payments table was the revenue source). Fall
        // back to live revenue so the page always shows real numbers.
        if (! $summary || (float) $summary->total_revenue == 0) {
            return $this->liveRevenue($request);
        }

        $byType = (clone $query)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->orderByDesc('total_amount')
            ->get();

        $bySource = (clone $query)
            ->select('revenue_source', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'), DB::raw('AVG(average_transaction_value) as avg_transaction'))
            ->groupBy('revenue_source')
            ->orderByDesc('total_amount')
            ->get();

        $byDate = (clone $query)
            ->select('date', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reports.revenue', compact('summary', 'byType', 'bySource', 'byDate'));
    }

    public function export(Request $request)
    {
        $query = DailyReport::query();

        if ($request->filled('date_from')) {
            $query->where('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('report_date', '<=', $request->date_to);
        }

        $reports = $query->orderByDesc('report_date')->get();

        $filename = 'daily_reports_'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Date', 'Total Orders', 'Completed Orders', 'Cancelled Orders',
                'New Users', 'New Drivers', 'Total Revenue', 'Avg Fare', 'Total Distance (km)',
            ]);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->report_date->format('Y-m-d'),
                    $report->total_orders,
                    $report->completed_orders,
                    $report->cancelled_orders,
                    $report->new_users,
                    $report->new_drivers,
                    $report->total_revenue,
                    $report->avg_fare,
                    $report->total_distance_km,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function liveRevenue(Request $request)
    {
        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->subDays(30)->startOfDay();
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();

        [$paid, $source] = $this->revenueSource($from, $to);

        $total = $this->revenueOf($paid, $source);
        $count = (int) (clone $paid)->count();

        if ($source === 'orders') {
            $byDate = (clone $paid)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as total_amount'),
                    DB::raw('COUNT(*) as total_transactions')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => (object) [
                    'date' => Carbon::parse($row->date),
                    'total_amount' => $row->total_amount,
                    'total_transactions' => $row->total_transactions,
                ]);

            $bySource = (clone $paid)
                ->select('payment_method as revenue_source', DB::raw('SUM(COALESCE(actual_fare, estimated_fare, 0)) as total_amount'), DB::raw('COUNT(*) as total_transactions'), DB::raw('AVG(actual_fare) as avg_transaction'))
                ->groupBy('payment_method')
                ->orderByDesc('total_amount')
                ->get();
        } else {
            $byDate = (clone $paid)
                ->select(
                    DB::raw('DATE(COALESCE(paid_at, created_at)) as date'),
                    DB::raw('SUM(amount) as total_amount'),
                    DB::raw('COUNT(*) as total_transactions')
                )
                ->groupBy(DB::raw('DATE(COALESCE(paid_at, created_at))'))
                ->orderBy('date')
                ->get()
                ->map(fn ($row) => (object) [
                    'date' => Carbon::parse($row->date),
                    'total_amount' => $row->total_amount,
                    'total_transactions' => $row->total_transactions,
                ]);

            $bySource = (clone $paid)
                ->select('method as revenue_source', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_transactions'), DB::raw('AVG(amount) as avg_transaction'))
                ->groupBy('method')
                ->orderByDesc('total_amount')
                ->get();
        }

        $byType = collect([
            (object) ['revenue_type' => 'gross', 'total_amount' => $total, 'total_transactions' => $count],
        ]);

        $summary = (object) [
            'total_revenue' => $total,
            'total_transactions' => $count,
            'avg_transaction' => $count > 0 ? $total / $count : 0,
            'days_with_data' => $byDate->count(),
        ];

        return view('admin.reports.revenue', compact('summary', 'byType', 'bySource', 'byDate'));
    }

    /**
     * Revenue source query. When paid orders exist we read revenue from the
     * orders table (payment_status = 'paid'); otherwise we fall back to the
     * legacy payments table. The prod payments table is empty, while orders
     * carry the real paid amounts — so orders is the primary source.
     *
     * @return array{0: Builder, 1: string}  [query, 'orders'|'payments']
     */
    private function revenueSource(?\Carbon\Carbon $from = null, ?\Carbon\Carbon $to = null): array
    {
        if (Order::where('payment_status', 'paid')->exists()) {
            $query = Order::where('payment_status', 'paid')
                ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->where('created_at', '<=', $to));

            return [$query, 'orders'];
        }

        $query = Payment::where('status', 'paid')
            ->when($from || $to, function ($q) use ($from, $to) {
                if ($from && $to) {
                    $q->whereBetween('paid_at', [$from, $to])
                        ->orWhere(fn ($fb) => $fb->whereNull('paid_at')->whereBetween('created_at', [$from, $to]));
                } elseif ($from) {
                    $q->where('paid_at', '>=', $from)
                        ->orWhere(fn ($fb) => $fb->whereNull('paid_at')->where('created_at', '>=', $from));
                } else {
                    $q->where('paid_at', '<=', $to)
                        ->orWhere(fn ($fb) => $fb->whereNull('paid_at')->where('created_at', '<=', $to));
                }
            });

        return [$query, 'payments'];
    }

    private function revenueOf(Builder $query, string $source): float
    {
        $expression = $source === 'orders'
            ? DB::raw('COALESCE(actual_fare, estimated_fare, 0)')
            : DB::raw('amount');

        return (float) (clone $query)->sum($expression);
    }
}
