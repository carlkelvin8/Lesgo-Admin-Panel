@extends('admin.layouts.app')
@section('title', 'Support Tickets - LesGo Admin')
@section('header', 'Support Tickets')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ticket # or subject..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                @foreach(['open','in_progress','waiting_customer','waiting_internal','resolved','closed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Priority</option>
                @foreach(['low','medium','high','urgent'] as $p)
                    <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                            @php $pc = ['low'=>'bg-gray-100 text-gray-700','medium'=>'bg-blue-100 text-blue-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700']; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $pc[$ticket->priority] ?? 'bg-gray-100' }}">{{ ucfirst($ticket->priority) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php $sc2 = ['open'=>'bg-yellow-100 text-yellow-800','in_progress'=>'bg-blue-100 text-blue-800','resolved'=>'bg-green-100 text-green-800','closed'=>'bg-gray-100 text-gray-800','cancelled'=>'bg-red-100 text-red-800']; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $sc2[$ticket->status] ?? 'bg-gray-100' }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $ticket->assignee->name ?? 'Unassigned' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No tickets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $tickets->links() }}</div>
</div>
@endsection
