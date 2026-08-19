@extends('admin.layouts.app')
@section('title', 'Reports - LesGo Admin')
@section('header', 'Reports')

@section('actions')
<form method="POST" action="{{ route('admin.reports.generate') }}" class="flex gap-2">@csrf<input type="date" name="report_date" value="{{ now()->subDay()->toDateString() }}" max="{{ now()->toDateString() }}" required class="border rounded-lg px-3 py-2 text-sm"><button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-rotate mr-1"></i> Generate</button></form>
<a href="{{ route('admin.reports.export', request()->only(['date_from', 'date_to'])) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-download mr-1"></i> Export CSV</a>
@endsection

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
        <a href="{{ route('admin.reports.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Total Orders</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Completed</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Cancelled</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">New Users</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">New Drivers</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Revenue</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Avg Fare</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $report->report_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($report->total_orders) }}</td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-green-600 font-medium">{{ number_format($report->completed_orders) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-red-600 font-medium">{{ number_format($report->cancelled_orders) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($report->new_users) }}</td>
                        <td class="px-6 py-4 text-right text-gray-700">{{ number_format($report->new_drivers) }}</td>
                        <td class="px-6 py-4 text-right font-medium text-gray-800">₱{{ number_format($report->total_revenue, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-600">₱{{ number_format($report->avg_fare, 2) }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.reports.daily', $report->report_date->format('Y-m-d')) }}" class="text-blue-600 hover:text-blue-800" title="View Details"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-gray-400">No daily reports found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $reports->links() }}</div>
</div>
@endsection
