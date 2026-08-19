<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeliverAdminNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $notificationId)
    {
        $this->onQueue('notifications');
    }

    public function handle(NotificationDeliveryService $delivery): void
    {
        $notification = Notification::with('user')->find($this->notificationId);

        if (! $notification || $notification->delivery_status === 'delivered') {
            return;
        }

        $notification->update([
            'delivery_status' => 'processing',
            'delivery_attempts' => $notification->delivery_attempts + 1,
            'failure_reason' => null,
            'failed_at' => null,
        ]);

        try {
            $result = $delivery->deliver($notification);

            $notification->update([
                'delivery_status' => 'delivered',
                'delivered_via' => $result['via'],
                'delivery_reference' => filled($result['reference'])
                    ? mb_substr($result['reference'], 0, 255)
                    : null,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $notification->update([
                'delivery_status' => 'retrying',
                'failure_reason' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Notification::whereKey($this->notificationId)->update([
            'delivery_status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => mb_substr($exception?->getMessage() ?: 'Delivery job failed.', 0, 2000),
        ]);
    }
}
