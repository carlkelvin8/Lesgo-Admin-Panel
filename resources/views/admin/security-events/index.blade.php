@extends('admin.layouts.app')
@section('title', 'Security Events - LesGo Admin')
@section('header', 'Security Events')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Open events</p><p class="text-3xl font-bold text-orange-600">{{ number_format($summary['open']) }}</p></div>
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Open critical</p><p class="text-3xl font-bold text-red-600">{{ number_format($summary['critical']) }}</p></div>
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Resolved today</p><p class="text-3xl font-bold text-green-600">{{ number_format($summary['resolved_today']) }}</p></div>
</div>
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Event, description, IP, or user" value="{{ request('search') }}" />
        <x-filter-input name="severity" label="Severity" type="select" :options="['' => 'All', 'info' => 'Info', 'warning' => 'Warning', 'high' => 'High', 'critical' => 'Critical']" value="{{ request('severity') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'open' => 'Open', 'resolved' => 'Resolved']" value="{{ request('status') }}" />
    </x-filter-panel>
</div>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="responsive-table w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-6 py-3">Severity</th><th class="text-left px-6 py-3">Event</th><th class="text-left px-6 py-3">Actor / Source</th><th class="text-left px-6 py-3">Status</th><th class="text-left px-6 py-3">Detected</th><th class="px-6 py-3"></th></tr></thead><tbody class="divide-y">
        @forelse($events as $event)
        <tr><td class="px-6 py-4"><x-status-badge :status="$event->severity" /></td><td class="px-6 py-4"><p class="font-medium">{{ str_replace('_', ' ', $event->event_type) }}</p><p class="text-xs text-gray-500 max-w-md truncate">{{ $event->description ?: 'No description' }}</p></td><td class="px-6 py-4"><p>{{ $event->user->name ?? 'Unknown user' }}</p><p class="text-xs text-gray-500">{{ $event->source ?? '—' }} · {{ $event->ip_address ?? 'No IP' }}</p></td><td class="px-6 py-4"><x-status-badge :status="$event->is_resolved ? 'resolved' : 'unresolved'" /></td><td class="px-6 py-4 text-xs text-gray-500">{{ $event->detected_at?->format('M d, Y H:i') }}</td><td class="px-6 py-4 text-right"><a href="{{ route('admin.security-events.show', $event) }}" class="text-blue-600">Investigate</a></td></tr>
        @empty<tr><td colspan="6"><x-empty-state icon="fa-shield-halved" title="No security events found" description="There are no security events matching your filters." /></td></tr>@endforelse
    </tbody></table>
    @if($events->hasPages())<div class="px-6 py-4 border-t">{{ $events->links() }}</div>@endif
</div>
@endsection
