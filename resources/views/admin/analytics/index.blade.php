@extends('admin.layouts.app')
@section('title', 'Analytics - LesGo Admin')
@section('header', 'Analytics Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue (30d)</p>
                <p class="text-2xl font-bold text-gray-800">₱{{ number_format($stats['total_revenue'], 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Orders (30d)</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_orders']) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Avg Daily Revenue</p>
                <p class="text-2xl font-bold text-gray-800">₱{{ number_format($stats['avg_daily_revenue'], 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">New Users (30d)</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_new_users']) }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-plus text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie mr-2 text-gray-400"></i>Revenue by Type (30d)</h3>
        @forelse($revenueByType as $type)
            <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                <div class="flex items-center gap-3">
                    @php
                        $typeIcons = [
                            'ride' => ['icon' => 'fa-motorcycle', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
                            'delivery' => ['icon' => 'fa-truck', 'color' => 'text-green-600', 'bg' => 'bg-green-100'],
                            'food' => ['icon' => 'fa-utensils', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100'],
                            'subscription' => ['icon' => 'fa-crown', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-100'],
                            'commission' => ['icon' => 'fa-percent', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100'],
                        ];
                        $t = $typeIcons[$type->revenue_type] ?? ['icon' => 'fa-dollar-sign', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100'];
                    @endphp
                    <div class="w-8 h-8 {{ $t['bg'] }} rounded-full flex items-center justify-center">
                        <i class="fas {{ $t['icon'] }} {{ $t['color'] }}" style="font-size: 12px"></i>
                    </div>
                    <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $type->revenue_type) }}</span>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-800">₱{{ number_format($type->total_amount, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($type->total_transactions) }} txns</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No revenue data available.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-chart-bar mr-2 text-gray-400"></i>Daily Revenue Trend (7d)</h3>
        @forelse($dailyRevenueTrend as $day)
            <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm text-gray-700">{{ $day->report_date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($day->total_orders) }} orders</p>
                </div>
                <p class="text-sm font-semibold text-green-600">₱{{ number_format($day->total_revenue, 2) }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No daily report data available.</p>
        @endforelse
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-bolt mr-2 text-gray-400"></i>Today's Metrics</h3>
        @forelse($todayMetrics as $type => $metrics)
            <div class="mb-4 last:mb-0">
                <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2">{{ str_replace('_', ' ', $type) }}</h4>
                @foreach($metrics as $metric)
                    <div class="flex items-center justify-between py-2 pl-4 border-l-2 border-gray-200">
                        <span class="text-sm text-gray-600">{{ str_replace('_', ' ', $metric->metric_key) }}</span>
                        <span class="text-sm font-medium text-gray-800">{{ number_format($metric->metric_value, 2) }}</span>
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No metrics recorded today.</p>
        @endforelse
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-calendar-check mr-2 text-gray-400"></i>Recent Daily Reports</h3>
        @forelse($recentReports as $report)
            <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                <div>
                    <p class="text-sm text-gray-700">{{ $report->report_date->format('M d, Y') }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($report->completed_orders) }}/{{ number_format($report->total_orders) }} completed</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-green-600">₱{{ number_format($report->total_revenue, 2) }}</p>
                    <p class="text-xs text-gray-500">{{ number_format($report->total_distance_km, 1) }} km</p>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400 text-center py-4">No daily reports generated yet.</p>
        @endforelse
    </div>
</div>

@if($eventStats->count())
<div class="bg-white rounded-xl shadow-sm p-6">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-mouse-pointer mr-2 text-gray-400"></i>Top Events (7d)</h3>
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Event Type</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Count</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($eventStats as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-700 capitalize">{{ str_replace('_', ' ', $event->event_type) }}</td>
                        <td class="px-6 py-3 text-right font-medium text-gray-800">{{ number_format($event->count) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
