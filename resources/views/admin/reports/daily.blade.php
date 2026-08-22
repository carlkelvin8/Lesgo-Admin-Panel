@extends('admin.layouts.app')
@section('title', 'Daily Report - LesGo Admin')
@section('header', 'Daily Report - ' . $report->report_date->format('M d, Y'))

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-800">₱{{ number_format($report->total_revenue, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($report->total_orders) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">New Users</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($report->new_users) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-plus text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">New Drivers</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($report->new_drivers) }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-motorcycle text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Avg Fare</p>
        <p class="text-3xl font-bold text-gray-800">₱{{ number_format($report->avg_fare, 2) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Total Distance</p>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($report->total_distance_km, 1) }} <span class="text-base font-normal text-gray-500">km</span></p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6">
        <p class="text-sm text-gray-500">Completion Rate</p>
        @php
            $completionRate = $report->total_orders > 0 ? round(($report->completed_orders / $report->total_orders) * 100, 1) : 0;
        @endphp
        <p class="text-3xl font-bold text-gray-800">{{ $completionRate }}%</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Order Breakdown</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-600">Completed</span>
                        <span class="text-sm font-medium text-green-600">{{ number_format($report->completed_orders) }} ({{ $completionRate }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-600">Cancelled</span>
                        @php
                            $cancelRate = $report->total_orders > 0 ? round(($report->cancelled_orders / $report->total_orders) * 100, 1) : 0;
                        @endphp
                        <span class="text-sm font-medium text-red-600">{{ number_format($report->cancelled_orders) }} ({{ $cancelRate }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-500 h-2 rounded-full" style="width: {{ $cancelRate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm text-gray-600">Other (In Progress / Pending)</span>
                        @php
                            $otherOrders = $report->total_orders - $report->completed_orders - $report->cancelled_orders;
                            $otherRate = $report->total_orders > 0 ? round(($otherOrders / $report->total_orders) * 100, 1) : 0;
                        @endphp
                        <span class="text-sm font-medium text-blue-600">{{ number_format($otherOrders) }} ({{ $otherRate }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $otherRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Revenue Details</h3>
        </div>
        <div class="p-6">
            @forelse($revenueDetails as $detail)
                <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                    <div class="flex items-center gap-3">
                        <x-status-badge :status="$detail->revenue_type" />
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800">₱{{ number_format($detail->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($detail->total_transactions) }} txns</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">No revenue data for this date.</p>
            @endforelse
        </div>
    </div>
</div>

@if($report->meta && count($report->meta))
<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-code mr-2 text-gray-400"></i>Additional Data</h3>
    <div class="bg-gray-50 rounded-lg p-4">
        <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($report->meta, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endif

@if($metrics->count())
<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-800">Detailed Metrics</h3>
    </div>
    <div class="p-6">
        @foreach($metrics as $type => $typeMetrics)
            <div class="mb-6 last:mb-0">
                <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-3">{{ str_replace('_', ' ', $type) }}</h4>
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="text-left px-4 py-2 text-gray-500 font-medium">Category</th>
                                <th class="text-left px-4 py-2 text-gray-500 font-medium">Metric</th>
                                <th class="text-right px-4 py-2 text-gray-500 font-medium">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($typeMetrics as $metric)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 capitalize">{{ str_replace('_', ' ', $metric->metric_category) }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ str_replace('_', ' ', $metric->metric_key) }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format($metric->metric_value, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="flex justify-end">
    <a href="{{ route('admin.reports.index') }}" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to Reports</a>
</div>
@endsection
