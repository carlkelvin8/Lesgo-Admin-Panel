@extends('admin.layouts.app')
@section('title', 'Audit Logs - LesGo Admin')
@section('header', 'Audit Logs')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="User, action, resource..." value="{{ request('search') }}" />
        <x-filter-input name="event_category" label="Category" type="select" :options="['' => 'All Categories', 'authentication' => 'Authentication', 'authorization' => 'Authorization', 'data_modification' => 'Data Modification', 'system' => 'System', 'security' => 'Security', 'user_activity' => 'User Activity']" value="{{ request('event_category') }}" />
        <x-filter-input name="risk_level" label="Risk Level" type="select" :options="['' => 'All Levels', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']" value="{{ request('risk_level') }}" />
        <x-filter-input name="is_suspicious" label="Suspicious" type="select" :options="['' => 'All', 'yes' => 'Yes', 'no' => 'No']" value="{{ request('is_suspicious') }}" />
        <x-filter-input name="date_from" label="From" type="date" value="{{ request('date_from') }}" />
        <x-filter-input name="date_to" label="To" type="date" value="{{ request('date_to') }}" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
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
                            <x-status-badge :status="$log->risk_level" />
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
                    <tr><td colspan="8"><x-empty-state icon="fa-clock-rotate-left" title="No audit logs found" description="There are no audit logs matching your filters." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">
        {{ $logs->links() }}
    </div>
</div>
@endsection
