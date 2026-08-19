@extends('admin.layouts.app')
@section('title', 'Ticket Details - LesGo Admin')
@section('header', 'Ticket: ' . $ticket->ticket_number)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Ticket Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Ticket Information</h3>
        @php
            $pc = ['low'=>'bg-gray-100 text-gray-700','medium'=>'bg-blue-100 text-blue-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
            $sc = ['open'=>'bg-yellow-100 text-yellow-800','in_progress'=>'bg-blue-100 text-blue-800','resolved'=>'bg-green-100 text-green-800','closed'=>'bg-gray-100 text-gray-800','cancelled'=>'bg-red-100 text-red-800'];
        @endphp
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Ticket #</span><span class="font-mono text-blue-600">{{ $ticket->ticket_number }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Subject</span><span class="text-right max-w-[180px]">{{ $ticket->subject }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Category</span><span>{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Priority</span><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pc[$ticket->priority] ?? '' }}">{{ ucfirst($ticket->priority) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Status</span><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$ticket->status] ?? '' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">User</span><span>{{ $ticket->user->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Assigned To</span><span>{{ $ticket->assignee->name ?? 'Unassigned' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Created</span><span>{{ $ticket->created_at->format('M d, Y H:i') }}</span></div>
        </div>
    </div>

    <!-- Update + Description -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Description</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $ticket->description }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Conversation</h3>
            <div class="space-y-3 mb-5">
                @forelse($ticket->messages as $message)
                    <div class="rounded-lg p-4 {{ $message->is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50' }}">
                        <div class="flex justify-between gap-3 mb-2">
                            <p class="text-sm font-medium">{{ $message->user->name ?? 'Deleted user' }} @if($message->is_internal)<span class="ml-2 text-xs text-yellow-700">Internal note</span>@endif</p>
                            <p class="text-xs text-gray-500">{{ $message->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $message->message }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No replies yet.</p>
                @endforelse
            </div>
            <form method="POST" action="{{ route('admin.tickets.messages.store', $ticket) }}">
                @csrf
                <label class="block text-sm font-medium mb-1">Reply or internal note</label>
                <textarea name="message" rows="4" required class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-3" placeholder="Write a response..."></textarea>
                <div class="flex items-center justify-between gap-3">
                    <label class="text-sm"><input type="checkbox" name="is_internal" value="1"> Internal note (hidden from customer)</label>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm">Post Message</button>
                </div>
            </form>
        </div>

        <!-- Update Form -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Update Ticket</h3>
            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach(['open','in_progress','waiting_customer','waiting_internal','resolved','closed','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Priority</label>
                        <select name="priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach(['low','medium','high','urgent'] as $p)
                                <option value="{{ $p }}" {{ $ticket->priority === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Assign To</label>
                        <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Unassigned</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" @selected($ticket->assigned_to === $admin->id)>{{ $admin->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Update Ticket</button>
            </form>
        </div>

        @if($ticket->order)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-2">Related Order</h3>
            <a href="{{ route('admin.orders.show', $ticket->order) }}" class="text-blue-600 hover:underline text-sm">Order #{{ $ticket->order->id }} - {{ ucfirst($ticket->order->status) }}</a>
        </div>
        @endif
    </div>
</div>
@endsection
