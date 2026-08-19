@extends('admin.layouts.app')
@section('title', 'Notification Details - LesGo Admin')
@section('header', 'Notification Details')

@section('actions')
@if(!$notification->read_at)
<form method="POST" action="{{ route('admin.notifications.read', $notification) }}">@csrf @method('PATCH')<button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Mark Read</button></form>
@endif
@if($notification->delivery_status !== 'delivered')
<form method="POST" action="{{ route('admin.notifications.retry', $notification) }}">@csrf<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Retry Delivery</button></form>
@endif
<form method="POST" action="{{ route('admin.notifications.destroy', $notification) }}" onsubmit="return confirm('Delete this notification?')">@csrf @method('DELETE')<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete</button></form>
@endsection

@section('content')
<div class="max-w-4xl grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6"><p class="text-xs uppercase tracking-wide text-gray-500 mb-2">{{ $notification->type }}</p><h3 class="text-xl font-bold text-gray-900 mb-4">{{ $notification->title }}</h3><p class="text-gray-700 whitespace-pre-wrap">{{ $notification->body }}</p></div>
    <div class="bg-white rounded-xl shadow-sm p-6 text-sm space-y-3">
        <div><p class="text-gray-500 text-xs">Recipient</p><p class="font-medium">{{ $notification->user->name ?? 'Deleted user' }}</p><p class="text-gray-500">{{ $notification->user->email ?? '—' }}</p></div>
        <div><p class="text-gray-500 text-xs">Channel</p><p>{{ strtoupper(str_replace('_', ' ', $notification->channel)) }}</p></div>
        <div><p class="text-gray-500 text-xs">Delivery</p><p class="font-medium">{{ ucfirst($notification->delivery_status ?? 'pending') }}</p><p class="text-xs text-gray-500">{{ $notification->delivery_attempts }} attempt(s) · {{ $notification->delivered_via ?: 'No provider response yet' }}</p>@if($notification->delivery_reference)<p class="mt-1 break-all font-mono text-[10px] text-gray-400">{{ $notification->delivery_reference }}</p>@endif</div>
        @if($notification->sent_at)<div><p class="text-gray-500 text-xs">Sent</p><p>{{ $notification->sent_at->format('M d, Y H:i') }}</p></div>@endif
        @if($notification->failure_reason)<div class="rounded-lg bg-red-50 p-3 text-red-700"><p class="text-xs font-semibold">Last delivery error</p><p class="mt-1 text-xs break-words">{{ $notification->failure_reason }}</p></div>@endif
        <div><p class="text-gray-500 text-xs">Created</p><p>{{ $notification->created_at->format('M d, Y H:i') }}</p></div>
        <div><p class="text-gray-500 text-xs">Read</p><p>{{ $notification->read_at?->format('M d, Y H:i') ?? 'Not yet' }}</p></div>
        @if($notification->data)<div><p class="text-gray-500 text-xs mb-1">Data</p><pre class="bg-gray-50 rounded p-2 text-xs overflow-auto">{{ json_encode($notification->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>@endif
    </div>
</div>
@endsection
