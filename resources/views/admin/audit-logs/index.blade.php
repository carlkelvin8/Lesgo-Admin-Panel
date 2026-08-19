@extends('admin.layouts.app')
@section('title', 'Audit Logs - LesGo Admin')
@section('header', 'Audit Logs')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="User, action, resource..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Category</label>
            <select name="event_category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <option value="">All Categories</option>
                @foreach(['authentication','authorization','data_modification','system','security','user_activity'] as $cat)
                    <option value="{{ $cat }}" {{ request('event_category') === $cat ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Risk Level</label>
            <select name="risk_level" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <option value="">All Levels</option>
                @foreach(['low','medium','high','critical'] as $r)
                    <option value="{{ $r }}" {{ request('risk_level') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Suspicious</label>
            <select name="is_suspicious" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <option value="">All</option>
                <option value="yes" {{ request('is_suspicious') === 'yes' ? 'selected' : '' }}>Yes</option>
                <option value="no" {{ request('is_suspicious') === 'no' ? 'selected' : '' }}>No</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('admin.audit-logs.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Timestamp</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Action</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Resource</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Risk Level</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">IP Address</th>
                    <th class="text-center px-6 py-3 text-gray-500 font-medium">Suspicious</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php
                    $riskColors = [
                        'low' => 'bg-green-100 text-green-800',
                        'medium' => 'bg-yellow-100 text-yellow-800',
                        'high' => 'bg-orange-100 text-orange-800',
                        'critical' => 'bg-red-100 text-red-800',
                    ];
                @endphp
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $log->occurred_at?->format('M d, Y H:i:s') ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($log->user)
                                <div>
                                    <p class="font-medium text-gray-800">{{ $log->user->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->user->email }}</p>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">System</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-800 max-w-[200px] truncate">{{ $log->action }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">{{ $log->resource_type }} #{{ $log->resource_id ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $riskColors[$log->risk_level] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst($log->risk_level) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs font-mono">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($log->is_suspicious)
                                <i class="fas fa-exclamation-triangle text-red-500" title="Suspicious"></i>
                            @else
                                <i class="fas fa-check-circle text-green-400" title="Normal"></i>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-blue-600 hover:text-blue-800" title="View Details"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No audit logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">
        {{ $logs->links() }}
    </div>
</div>
@endsection
