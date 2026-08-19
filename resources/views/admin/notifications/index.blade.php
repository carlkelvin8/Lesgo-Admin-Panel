@extends('admin.layouts.app')
@section('title', 'Notifications - LesGo Admin')
@section('header', 'Notifications')

@section('actions')
<a href="{{ route('admin.notifications.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-paper-plane mr-1"></i> Publish Notification</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input name="search" value="{{ request('search') }}" placeholder="Title or message" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Channel</label>
            <select name="channel" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All channels</option>
                @foreach(['in_app','push','sms','email'] as $channel)
                    <option value="{{ $channel }}" @selected(request('channel') === $channel)>{{ strtoupper(str_replace('_', ' ', $channel)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Read status</label>
            <select name="read_status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="unread" @selected(request('read_status') === 'unread')>Unread</option>
                <option value="read" @selected(request('read_status') === 'read')>Read</option>
            </select>
        </div>
        <button class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.notifications.index') }}" class="text-gray-500 px-3 py-2 text-sm">Clear</a>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Recipient</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Notification</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Type / Channel</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Created</th>
                <th class="px-6 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4"><p class="font-medium">{{ $notification->user->name ?? 'Deleted user' }}</p><p class="text-xs text-gray-500">{{ $notification->user->email ?? '—' }}</p></td>
                        <td class="px-6 py-4 max-w-md"><p class="font-medium text-gray-800">{{ $notification->title }}</p><p class="text-xs text-gray-500 truncate">{{ $notification->body }}</p></td>
                        <td class="px-6 py-4"><p>{{ $notification->type }}</p><p class="text-xs text-gray-500">{{ strtoupper(str_replace('_', ' ', $notification->channel)) }}</p></td>
                        <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs {{ $notification->read_at ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span></td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $notification->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 text-right"><a href="{{ route('admin.notifications.show', $notification) }}" class="text-blue-600 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No notifications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($notifications->hasPages())<div class="px-6 py-4 border-t">{{ $notifications->links() }}</div>@endif
</div>
@endsection
