@extends('admin.layouts.app')
@section('title', 'Audit Log Detail - LesGo Admin')
@section('header', 'Audit Log Detail')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Event Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-clipboard-list mr-2 text-blue-600"></i>Event Information</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Timestamp</span>
                <span class="text-gray-800">{{ $auditLog->occurred_at?->format('M d, Y H:i:s') ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">User</span>
                <span class="text-gray-800">{{ $auditLog->user->name ?? 'System' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Event Type</span>
                <span class="text-gray-800 font-mono text-xs">{{ $auditLog->event_type ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Event Category</span>
                <span class="text-gray-800">{{ ucfirst(str_replace('_', ' ', $auditLog->event_category ?? '-')) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Action</span>
                <span class="text-gray-800 font-medium">{{ $auditLog->action }}</span>
            </div>
        </div>
    </div>

    <!-- Resource & Network Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-server mr-2 text-purple-600"></i>Resource & Network</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Resource Type</span>
                <span class="text-gray-800 font-mono">{{ $auditLog->resource_type ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Resource ID</span>
                <span class="text-gray-800 font-mono">{{ $auditLog->resource_id ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">IP Address</span>
                <span class="text-gray-800 font-mono">{{ $auditLog->ip_address ?? '-' }}</span>
            </div>
            <div class="border-b pb-2">
                <span class="text-gray-500">User Agent</span>
                <p class="text-gray-800 mt-1 text-xs break-all">{{ $auditLog->user_agent ?? '-' }}</p>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Session ID</span>
                <span class="text-gray-800 font-mono text-xs">{{ $auditLog->session_id ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Request ID</span>
                <span class="text-gray-800 font-mono text-xs">{{ $auditLog->request_id ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Risk Assessment -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-shield-alt mr-2 text-orange-600"></i>Risk Assessment</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Risk Level</span>
                <x-status-badge :status="$auditLog->risk_level" />
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Suspicious</span>
                @if($auditLog->is_suspicious)
                    <span class="text-red-600 font-medium"><i class="fas fa-exclamation-triangle mr-1"></i>Yes</span>
                @else
                    <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>No</span>
                @endif
            </div>
            @if(!empty($auditLog->context))
            <div>
                <span class="text-gray-500">Context</span>
                <pre class="mt-2 bg-gray-50 rounded-lg p-3 text-xs text-gray-700 overflow-x-auto">{{ json_encode($auditLog->context, JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
            @if(!empty($auditLog->metadata))
            <div>
                <span class="text-gray-500">Metadata</span>
                <pre class="mt-2 bg-gray-50 rounded-lg p-3 text-xs text-gray-700 overflow-x-auto">{{ json_encode($auditLog->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Changes Comparison -->
@if(!empty($auditLog->old_values) || !empty($auditLog->new_values))
<div class="bg-white rounded-xl shadow-sm p-6 mt-6">
    <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-exchange-alt mr-2 text-teal-600"></i>Changes</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2 font-medium">Old Values</h4>
            <div class="bg-red-50 rounded-lg p-4 border border-red-100">
                @if(!empty($auditLog->old_values))
                    <table class="responsive-table w-full text-sm">
                        <tbody class="divide-y divide-red-100">
                            @foreach($auditLog->old_values as $key => $value)
                                <tr>
                                    <td class="py-2 pr-4 font-mono text-xs text-red-700 whitespace-nowrap">{{ $key }}</td>
                                    <td class="py-2 text-red-600">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-400 italic">No previous values</p>
                @endif
            </div>
        </div>
        <div>
            <h4 class="text-xs uppercase tracking-wider text-gray-500 mb-2 font-medium">New Values</h4>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                @if(!empty($auditLog->new_values))
                    <table class="responsive-table w-full text-sm">
                        <tbody class="divide-y divide-green-100">
                            @foreach($auditLog->new_values as $key => $value)
                                <tr>
                                    <td class="py-2 pr-4 font-mono text-xs text-green-700 whitespace-nowrap">{{ $key }}</td>
                                    <td class="py-2 text-green-600">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-400 italic">No new values</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection
