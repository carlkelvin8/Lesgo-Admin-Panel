@extends('admin.layouts.app')
@section('title', 'Notifications - LesGo Admin')
@section('header', 'Notifications')

@section('actions')
<a href="{{ route('admin.notifications.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-paper-plane mr-1"></i> Publish Notification</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Title or message" />
        <x-filter-input name="channel" label="Channel" type="select" :options="['' => 'All channels', 'in_app' => 'In App', 'push' => 'Push', 'sms' => 'SMS', 'email' => 'Email']" />
        <x-filter-input name="read_status" label="Read status" type="select" :options="['' => 'All', 'unread' => 'Unread', 'read' => 'Read']" />
        <x-filter-input name="delivery_status" label="Delivery" type="select" :options="['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'retrying' => 'Retrying', 'delivered' => 'Delivered', 'failed' => 'Failed']" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
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
                        <td class="px-6 py-4">
                            <x-status-badge :status="$notification->delivery_status ?? 'pending'" />
                            <p class="mt-1 text-[11px] text-gray-400">{{ $notification->read_at ? 'Read' : 'Unread' }}</p>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $notification->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 text-right"><a href="{{ route('admin.notifications.show', $notification) }}" class="text-blue-600 hover:underline">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="fa-bell" title="No notifications found" description="There are no notifications to display." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($notifications->hasPages())<div class="px-6 py-4 border-t">{{ $notifications->links() }}</div>@endif
</div>
@endsection
