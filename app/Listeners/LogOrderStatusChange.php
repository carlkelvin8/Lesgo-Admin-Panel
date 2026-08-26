<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Models\SecurityEvent;

class LogOrderStatusChange
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->newStatus === 'cancelled') {
            SecurityEvent::create([
                'event_type' => 'order_cancelled',
                'severity' => 'low',
                'source' => 'order_management',
                'description' => "Order #{$event->order->id} was cancelled. Previous status: {$event->previousStatus}.",
                'event_data' => [
                    'order_id' => $event->order->id,
                    'from' => $event->previousStatus,
                    'to' => $event->newStatus,
                ],
                'detected_at' => now(),
            ]);
        }
    }
}
