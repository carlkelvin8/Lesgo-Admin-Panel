<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class NotificationDeliveryService
{
    public function __construct(private FirebaseCloudMessaging $firebase) {}

    /**
     * @return array{via: string, reference: ?string}
     */
    public function deliver(Notification $notification): array
    {
        $notification->loadMissing('user');

        return match ($notification->channel) {
            'in_app' => ['via' => 'database', 'reference' => null],
            'email' => ['via' => $this->email($notification), 'reference' => null],
            'push' => ['via' => 'firebase-http-v1', 'reference' => $this->firebase->send($notification)],
            'sms' => ['via' => 'sms-webhook', 'reference' => $this->sms($notification)],
            default => throw new RuntimeException("Unsupported notification channel: {$notification->channel}"),
        };
    }

    private function email(Notification $notification): string
    {
        if (blank($notification->user?->email)) {
            throw new RuntimeException('The recipient does not have an email address.');
        }

        Mail::raw($notification->body, function ($message) use ($notification) {
            $message->to($notification->user->email, $notification->user->name)
                ->subject($notification->title);
        });

        return 'laravel-mail';
    }

    private function sms(Notification $notification): string
    {
        $url = config('services.sms.webhook_url');

        if (blank($url)) {
            throw new RuntimeException('SMS_WEBHOOK_URL is not configured.');
        }

        if (blank($notification->user?->phone_number)) {
            throw new RuntimeException('The recipient does not have a phone number.');
        }

        $request = Http::acceptJson()->timeout(20);
        if (filled(config('services.sms.webhook_token'))) {
            $request = $request->withToken(config('services.sms.webhook_token'));
        }

        $response = $request->post($url, [
            'to' => $notification->user->phone_number,
            'title' => $notification->title,
            'message' => $notification->body,
            'data' => $notification->data,
            'notification_id' => $notification->id,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('SMS provider delivery failed: '.$response->body());
        }

        return $response->json('reference') ?: 'sms-webhook';
    }
}
