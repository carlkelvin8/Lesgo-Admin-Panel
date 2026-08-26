<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function updateStatus(Order $order, string $newStatus, ?string $cancelReason, int $adminId): void
    {
        $statusTimestamps = [
            'accepted' => 'accepted_at',
            'picked_up' => 'picked_up_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        $update = ['status' => $newStatus];

        if (isset($statusTimestamps[$newStatus])) {
            $update[$statusTimestamps[$newStatus]] = now();
        }

        if ($newStatus === 'cancelled' && filled($cancelReason)) {
            $update['cancel_reason'] = $cancelReason;
        }

        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $update, $newStatus, $previousStatus, $adminId) {
            $order->update($update);

            OrderTrackingEvent::create([
                'order_id' => $order->id,
                'user_id' => $adminId,
                'event_type' => 'order_status_changed',
                'event_title' => 'Status changed to ' . str_replace('_', ' ', $newStatus),
                'event_description' => "Admin changed the order status from {$previousStatus} to {$newStatus}.",
                'event_category' => 'order',
                'metadata' => ['from' => $previousStatus, 'to' => $newStatus],
                'is_visible_to_customer' => true,
                'is_milestone' => in_array($newStatus, ['accepted', 'picked_up', 'completed', 'cancelled'], true),
                'event_time' => now(),
            ]);
        });
    }
}
