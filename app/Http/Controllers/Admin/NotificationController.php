<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverAdminNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('read_status')) {
            if ($request->read_status === 'read') {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        return view('admin.notifications.index', compact('notifications'));
    }

    public function show(Notification $notification)
    {
        $notification->load('user');

        return view('admin.notifications.show', compact('notification'));
    }

    public function create()
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => 'required|in:user,role',
            'user_id' => 'required_if:recipient_type,user|nullable|exists:users,id',
            'recipient_role' => 'required_if:recipient_type,role|nullable|in:all,customer,driver,partner,admin',
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'channel' => 'required|in:in_app,push,sms,email',
            'data' => 'nullable|json',
        ]);

        $recipients = User::query()
            ->where('is_active', true)
            ->when(
                $validated['recipient_type'] === 'user',
                fn ($query) => $query->whereKey($validated['user_id']),
                fn ($query) => $query->when(
                    $validated['recipient_role'] !== 'all',
                    fn ($roleQuery) => $roleQuery->where('role', $validated['recipient_role'])
                )
            )
            ->pluck('id');

        if ($recipients->isEmpty()) {
            return back()->withInput()->withErrors(['recipient_type' => 'No active users match the selected audience.']);
        }

        $payload = filled($validated['data'] ?? null)
            ? json_decode($validated['data'], true, 512, JSON_THROW_ON_ERROR)
            : null;

        DB::transaction(function () use ($recipients, $validated, $payload) {
            foreach ($recipients as $userId) {
                $notification = Notification::create([
                    'user_id' => $userId,
                    'type' => $validated['type'],
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'channel' => $validated['channel'],
                    'data' => $payload,
                ]);

                DeliverAdminNotification::dispatch($notification->id)->afterCommit();
            }
        });

        return redirect()->route('admin.notifications.index')
            ->with('success', "Notification published to {$recipients->count()} recipient(s).");
    }

    public function markRead(Notification $notification)
    {
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Notification deleted successfully.');
    }

    public function retry(Notification $notification)
    {
        if ($notification->delivery_status === 'delivered') {
            return back()->withErrors(['notification' => 'This notification was already delivered.']);
        }

        $notification->update([
            'delivery_status' => 'pending',
            'delivered_via' => null,
            'delivery_reference' => null,
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        DeliverAdminNotification::dispatch($notification->id)->afterCommit();

        return back()->with('success', 'Notification delivery has been queued again.');
    }
}
