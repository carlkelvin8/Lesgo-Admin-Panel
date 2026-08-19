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

        return view('admin.reports.index', compact('reports'));
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
        $payments = Payment::where('status', 'paid')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('paid_at', [$start, $end])
                    ->orWhere(fn ($fallback) => $fallback->whereNull('paid_at')->whereBetween('created_at', [$start, $end]));
            });

        $values = [
            'total_orders' => (clone $orders)->count(),
            'completed_orders' => (clone $orders)->where('status', 'completed')->count(),
            'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
            'new_users' => User::whereBetween('created_at', [$start, $end])->count(),
            'new_drivers' => DriverProfile::whereBetween('created_at', [$start, $end])->count(),
            'total_revenue' => (clone $payments)->sum('amount'),
            'avg_fare' => (clone $orders)->where('status', 'completed')->avg('actual_fare') ?? 0,
            'total_distance_km' => (int) round(((clone $orders)->sum('actual_distance_m') ?? 0) / 1000),
        ];

        DB::transaction(function () use ($date, $values, $payments) {
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

            $transactionCount = (clone $payments)->count();
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
        $report = DailyReport::where('report_date', $date)->firstOrFail();

        $metrics = DailyMetric::where('date', $date)
            ->get()
            ->groupBy('metric_type');

        $revenueDetails = RevenueAnalytics::where('date', $date)
            ->select('revenue_type', DB::raw('SUM(amount) as total_amount'), DB::raw('SUM(transaction_count) as total_transactions'))
            ->groupBy('revenue_type')
            ->get();

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

        $summary = (clone $query)
            ->select(
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('SUM(transaction_count) as total_transactions'),
                DB::raw('AVG(average_transaction_value) as avg_transaction'),
                DB::raw('COUNT(DISTINCT date) as days_with_data')
            )
            ->first();

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
}
