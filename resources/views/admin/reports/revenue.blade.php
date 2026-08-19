@extends('admin.layouts.app')
@section('title', 'Revenue Report - LesGo Admin')
@section('header', 'Revenue Analytics')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('admin.reports.revenue') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-800">₱{{ number_format($summary->total_revenue ?? 0, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Transactions</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($summary->total_transactions ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-receipt text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Avg Transaction</p>
                <p class="text-2xl font-bold text-gray-800">₱{{ number_format($summary->avg_transaction ?? 0, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Days with Data</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($summary->days_with_data ?? 0) }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-chart-pie mr-2 text-gray-400"></i>Revenue by Type</h3>
        </div>
        <div class="overflow-x-auto">
            @forelse($byType as $type)
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50 last:border-0">
                    <div class="flex items-center gap-3">
                        @php
                            $typeColors = [
                                'ride' => 'bg-blue-100 text-blue-600',
                                'delivery' => 'bg-green-100 text-green-600',
                                'food' => 'bg-orange-100 text-orange-600',
                                'commission' => 'bg-purple-100 text-purple-600',
                                'subscription' => 'bg-yellow-100 text-yellow-600',
                            ];
                            $tc = $typeColors[$type->revenue_type] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tc }}">{{ ucfirst(str_replace('_', ' ', $type->revenue_type)) }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800">₱{{ number_format($type->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($type->total_transactions) }} txns</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-8">No revenue data available.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-store mr-2 text-gray-400"></i>Revenue by Source</h3>
        </div>
        <div class="overflow-x-auto">
            @forelse($bySource as $source)
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-gray-700">{{ $source->revenue_source ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">Avg ₱{{ number_format($source->avg_transaction, 2) }}/txn</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-800">₱{{ number_format($source->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($source->total_transactions) }} txns</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-8">No source data available.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-800"><i class="fas fa-calendar-alt mr-2 text-gray-400"></i>Daily Revenue Breakdown</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Revenue</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Transactions</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Avg per Txn</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($byDate as $day)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $day->date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-green-600">₱{{ number_format($day->total_amount, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($day->total_transactions) }}</td>
                        <td class="px-6 py-4 text-right text-gray-600">₱{{ $day->total_transactions > 0 ? number_format($day->total_amount / $day->total_transactions, 2) : '0.00' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-gray-400">No revenue data for the selected period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
