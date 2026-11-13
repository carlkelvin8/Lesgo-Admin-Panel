<?php

namespace Tests\Feature;

use App\Jobs\DeliverAdminNotification;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_sms_delivery_records_the_provider_reference_without_resending_delivered_jobs(): void
    {
        config(['services.sms.webhook_url' => 'https://sms.example.test/send', 'services.sms.webhook_token' => null]);
        Http::fake(['sms.example.test/*' => Http::response(['reference' => 'SMS-QA-001'])]);
        $notice = $this->notice('sms');
        $job = new DeliverAdminNotification($notice->id);
        $job->handle(app(NotificationDeliveryService::class));
        $job->handle(app(NotificationDeliveryService::class));
        $this->assertSame('delivered', $notice->fresh()->delivery_status);
        $this->assertSame(1, $notice->fresh()->delivery_attempts);
        $this->assertSame('SMS-QA-001', $notice->fresh()->delivery_reference);
        $this->assertNotNull($notice->fresh()->sent_at);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['notification_id'] === $notice->id && $request['message'] === 'Test delivery');
    }

    public function test_provider_failure_is_retried_and_final_failure_is_recorded(): void
    {
        config(['services.sms.webhook_url' => 'https://sms.example.test/send', 'services.sms.webhook_token' => null]);
        Http::fake(['sms.example.test/*' => Http::response(['error' => 'Unavailable'], 503)]);
        $notice = $this->notice('sms');
        $job = new DeliverAdminNotification($notice->id);
        try {
            $job->handle(app(NotificationDeliveryService::class));
            $this->fail('Provider failures must be surfaced to the queue worker.');
        } catch (RuntimeException $exception) {
            $this->assertSame('retrying', $notice->fresh()->delivery_status);
            $this->assertSame(1, $notice->fresh()->delivery_attempts);
            $job->failed($exception);
        }
        $this->assertSame('failed', $notice->fresh()->delivery_status);
        $this->assertNotNull($notice->fresh()->failed_at);
        $this->assertNotEmpty($notice->fresh()->failure_reason);
    }

    public function test_email_delivery_uses_the_isolated_array_mailer(): void
    {
        $notice = $this->notice('email');
        (new DeliverAdminNotification($notice->id))->handle(app(NotificationDeliveryService::class));
        $this->assertSame('delivered', $notice->fresh()->delivery_status);
        $this->assertSame('laravel-mail', $notice->fresh()->delivered_via);
        $this->assertCount(1, Mail::getSymfonyTransport()->messages());
        Http::assertNothingSent();
    }

    public function test_push_without_a_device_token_fails_cleanly_without_a_network_request(): void
    {
        $notice = $this->notice('push');
        try {
            (new DeliverAdminNotification($notice->id))->handle(app(NotificationDeliveryService::class));
            $this->fail('A push notification requires a device token.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('FCM registration token', $exception->getMessage());
        }
        $this->assertSame('retrying', $notice->fresh()->delivery_status);
        Http::assertNothingSent();
    }

    public function test_deleted_notifications_are_ignored_by_pending_delivery_jobs(): void
    {
        $notice = $this->notice('sms');
        $id = $notice->id;
        $notice->delete();
        (new DeliverAdminNotification($id))->handle(app(NotificationDeliveryService::class));
        Http::assertNothingSent();
        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    private function notice(string $channel): Notification
    {
        return Notification::create([
            'user_id' => User::factory()->create(['phone_number' => '09170000000', 'fcm_token' => null])->id,
            'type' => 'test', 'title' => 'QA notice', 'body' => 'Test delivery', 'channel' => $channel,
        ]);
    }
}
