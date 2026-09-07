<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'partner', 'driver']);

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
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

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $validated = $request->validated();

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

    public function uploadProof(Request $request, Order $order)
    {
        $validated = $request->validate([
            'proof_images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'proof_images' => 'required|array|min:1',
        ]);

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $urls = $order->proof_images ?? [];
        foreach ($request->file('proof_images') as $file) {
            $path = $file->store('order-proofs/'.$order->id, $disk);
            $urls[] = Storage::disk($disk)->url($path);
        }
        $order->update(['proof_images' => $urls, 'proof_uploaded_at' => now()]);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Proof images uploaded.');
    }

    public function export(Request $request)
    {
        $query = Order::with(['customer', 'partner', 'driver.user']);

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
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

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders_export_' . now()->format('Y-m-d_His') . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Customer', 'Partner', 'Driver', 'Status', 'Payment Status', 'Fare', 'Date']);

            $query->latest()->chunk(500, function ($orders) use ($file) {
                foreach ($orders as $order) {
                    fputcsv($file, [
                        $order->id,
                        $order->customer?->name ?? 'N/A',
                        $order->partner?->name ?? 'N/A',
                        $order->driver?->user?->name ?? 'N/A',
                        $order->status,
                        $order->payment_status,
                        $order->actual_fare ?? $order->estimated_fare,
                        $order->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
