<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'partner', 'driver']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('partner', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'partner', 'driver.user', 'service', 'payments', 'trackingEvents.user', 'lesbuyItems']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,driver_arrived,in_progress,picked_up,completed,cancelled',
            'cancel_reason' => 'required_if:status,cancelled|nullable|string|max:1000',
        ]);

        $statusTimestamps = [
            'accepted' => 'accepted_at',
            'picked_up' => 'picked_up_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        $update = ['status' => $validated['status']];

        if (isset($statusTimestamps[$validated['status']])) {
            $update[$statusTimestamps[$validated['status']]] = now();
        }

        if ($validated['status'] === 'cancelled' && ! empty($validated['cancel_reason'])) {
            $update['cancel_reason'] = $validated['cancel_reason'];
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $update, $validated, $previousStatus) {
            $order->update($update);

            OrderTrackingEvent::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'event_type' => 'order_status_changed',
                'event_title' => 'Status changed to '.str_replace('_', ' ', $validated['status']),
                'event_description' => "Admin changed the order status from {$previousStatus} to {$validated['status']}.",
                'event_category' => 'order',
                'metadata' => ['from' => $previousStatus, 'to' => $validated['status']],
                'is_visible_to_customer' => true,
                'is_milestone' => in_array($validated['status'], ['accepted', 'picked_up', 'completed', 'cancelled'], true),
                'event_time' => now(),
            ]);
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }
}
