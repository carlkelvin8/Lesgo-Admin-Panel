@extends('admin.layouts.app')
@section('title', 'Security Events - LesGo Admin')
@section('header', 'Security Events')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Open events</p><p class="text-3xl font-bold text-orange-600">{{ number_format($summary['open']) }}</p></div>
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Open critical</p><p class="text-3xl font-bold text-red-600">{{ number_format($summary['critical']) }}</p></div>
    <div class="bg-white rounded-xl shadow-sm p-5"><p class="text-sm text-gray-500">Resolved today</p><p class="text-3xl font-bold text-green-600">{{ number_format($summary['resolved_today']) }}</p></div>
</div>
<div class="bg-white rounded-xl shadow-sm p-4 mb-6"><form method="GET" class="flex flex-wrap gap-3 items-end"><div class="flex-1 min-w-[220px]"><label class="block text-xs text-gray-500 mb-1">Search</label><input name="search" value="{{ request('search') }}" placeholder="Event, description, IP, or user" class="w-full border rounded-lg px-3 py-2 text-sm"></div><div><label class="block text-xs text-gray-500 mb-1">Severity</label><select name="severity" class="border rounded-lg px-3 py-2 text-sm"><option value="">All</option>@foreach(['info','warning','high','critical'] as $severity)<option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ ucfirst($severity) }}</option>@endforeach</select></div><div><label class="block text-xs text-gray-500 mb-1">Status</label><select name="status" class="border rounded-lg px-3 py-2 text-sm"><option value="">All</option><option value="open" @selected(request('status') === 'open')>Open</option><option value="resolved" @selected(request('status') === 'resolved')>Resolved</option></select></div><button class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Filter</button><a href="{{ route('admin.security-events.index') }}" class="px-3 py-2 text-gray-500 text-sm">Clear</a></form></div>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-6 py-3">Severity</th><th class="text-left px-6 py-3">Event</th><th class="text-left px-6 py-3">Actor / Source</th><th class="text-left px-6 py-3">Status</th><th class="text-left px-6 py-3">Detected</th><th class="px-6 py-3"></th></tr></thead><tbody class="divide-y">
        @forelse($events as $event)
        @php($colors = ['info'=>'bg-blue-100 text-blue-700','warning'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','critical'=>'bg-red-100 text-red-700'])
        <tr><td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$event->severity] ?? 'bg-gray-100' }}">{{ ucfirst($event->severity) }}</span></td><td class="px-6 py-4"><p class="font-medium">{{ str_replace('_', ' ', $event->event_type) }}</p><p class="text-xs text-gray-500 max-w-md truncate">{{ $event->description ?: 'No description' }}</p></td><td class="px-6 py-4"><p>{{ $event->user->name ?? 'Unknown user' }}</p><p class="text-xs text-gray-500">{{ $event->source ?? '—' }} · {{ $event->ip_address ?? 'No IP' }}</p></td><td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs {{ $event->is_resolved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $event->is_resolved ? 'Resolved' : 'Open' }}</span></td><td class="px-6 py-4 text-xs text-gray-500">{{ $event->detected_at?->format('M d, Y H:i') }}</td><td class="px-6 py-4 text-right"><a href="{{ route('admin.security-events.show', $event) }}" class="text-blue-600">Investigate</a></td></tr>
        @empty<tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No security events found.</td></tr>@endforelse
    </tbody></table>
    @if($events->hasPages())<div class="px-6 py-4 border-t">{{ $events->links() }}</div>@endif
</div>
@endsection
