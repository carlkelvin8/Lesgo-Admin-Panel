@extends('admin.layouts.app')
@section('title', 'Revenue Report - LesGo Admin')
@section('header', 'Revenue Analytics')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="date_from" label="Date From" type="date" value="{{ request('date_from') }}" />
        <x-filter-input name="date_to" label="Date To" type="date" value="{{ request('date_to') }}" />
    </x-filter-panel>
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
                        <x-status-badge :status="$type->revenue_type" />
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
        <table class="responsive-table w-full text-sm">
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
                    <tr><td colspan="4"><x-empty-state icon="fa-file-lines" title="No revenue data" description="No revenue data for the selected period." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
