@extends('admin.layouts.app')
@section('title', 'Support Tickets - LesGo Admin')
@section('header', 'Support Tickets')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search ticket # or subject..." />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All Status', 'open' => 'Open', 'in_progress' => 'In Progress', 'waiting_customer' => 'Waiting Customer', 'waiting_internal' => 'Waiting Internal', 'resolved' => 'Resolved', 'closed' => 'Closed', 'cancelled' => 'Cancelled']" />
        <x-filter-input name="priority" label="Priority" type="select" :options="['' => 'All Priority', 'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Ticket #</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Subject</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Category</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Priority</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Assigned</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-blue-600">{{ $ticket->ticket_number }}</td>
                        <td class="px-6 py-4 text-gray-800 max-w-[200px] truncate">{{ $ticket->subject }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $ticket->user->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$ticket->priority" />
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$ticket->status" />
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $ticket->assignee->name ?? 'Unassigned' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-ticket" title="No tickets found" description="There are no support tickets to display." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $tickets->links() }}</div>
</div>
@endsection
