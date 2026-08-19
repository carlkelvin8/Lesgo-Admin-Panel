<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignee']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->latest()->paginate(20)->withQueryString();

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['user', 'assignee', 'order', 'messages.user']);
        $admins = User::where('role', 'admin')->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.tickets.show', compact('ticket', 'admins'));
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_customer,waiting_internal,resolved,closed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validated['status'] === 'resolved' && ! $ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        if ($validated['status'] === 'closed' && ! $ticket->closed_at) {
            $validated['closed_at'] = now();
        }

        $validated['last_activity_at'] = now();

        $ticket->update($validated);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }

    public function storeMessage(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
        ]);

        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        $update = ['last_activity_at' => now()];
        if (! $request->boolean('is_internal') && ! $ticket->first_response_at) {
            $update['first_response_at'] = now();
        }
        if (! $request->boolean('is_internal') && $ticket->status === 'open') {
            $update['status'] = 'waiting_customer';
        }
        $ticket->update($update);

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', $request->boolean('is_internal') ? 'Internal note added.' : 'Reply sent.');
    }
}
