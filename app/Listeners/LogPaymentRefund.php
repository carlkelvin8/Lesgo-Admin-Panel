<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PaymentRefunded;
use App\Models\SecurityEvent;

class LogPaymentRefund
{
    public function handle(PaymentRefunded $event): void
    {
        SecurityEvent::create([
            'event_type' => 'payment_refunded',
            'severity' => $event->isFullRefund ? 'medium' : 'low',
            'source' => 'payment_management',
            'description' => "Payment #{$event->payment->id} was refunded ₱" . number_format($event->refundAmount, 2) . ($event->isFullRefund ? ' (full refund)' : ' (partial refund)') . '.',
            'event_data' => [
                'payment_id' => $event->payment->id,
                'refund_amount' => $event->refundAmount,
                'is_full_refund' => $event->isFullRefund,
            ],
            'detected_at' => now(),
        ]);
    }
}
