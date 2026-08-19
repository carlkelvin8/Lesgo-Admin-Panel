<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use App\Models\DailyReport;
use App\Models\MenuCategory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\SecurityEvent;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
    }

    public function test_all_important_admin_indexes_are_available(): void
    {
        $routes = [
            'admin.dashboard', 'admin.users.index', 'admin.drivers.index', 'admin.partners.index',
            'admin.orders.index', 'admin.services.index', 'admin.payments.index', 'admin.wallets.index',
            'admin.tickets.index', 'admin.ratings.index', 'admin.notifications.index',
            'admin.faq.categories', 'admin.faq.articles', 'admin.document-verifications.index',
            'admin.security-events.index', 'admin.audit-logs.index', 'admin.analytics.index',
            'admin.reports.index',
            'admin.users.create', 'admin.drivers.create', 'admin.partners.create',
            'admin.services.create', 'admin.notifications.create',
            'admin.faq.categories.create', 'admin.faq.articles.create',
        ];

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_admin_can_publish_a_notification_to_a_role(): void
    {
        User::factory()->count(2)->create(['role' => 'customer', 'is_active' => true]);
        User::factory()->create(['role' => 'driver', 'is_active' => true]);

        $this->post(route('admin.notifications.store'), [
            'recipient_type' => 'role',
            'recipient_role' => 'customer',
            'type' => 'admin.announcement',
            'title' => 'Service advisory',
            'body' => 'A scheduled maintenance window is coming.',
            'channel' => 'in_app',
        ])->assertRedirect(route('admin.notifications.index'));

        $this->assertSame(2, Notification::count());
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->admin->id]);
    }

    public function test_inactive_admin_cannot_log_in_and_repeated_failures_create_a_security_event(): void
    {
        auth()->logout();
        $inactive = User::factory()->create(['role' => 'admin', 'is_active' => false]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('admin.login.post'), [
                'email' => $inactive->email,
                'password' => 'password',
            ])->assertSessionHasErrors('email');
        }

        $this->assertGuest();
        $this->assertDatabaseCount('failed_login_attempts', 5);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'repeated_failed_admin_login',
            'severity' => 'high',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_login_page_supports_remember_me_and_recovery_navigation(): void
    {
        auth()->logout();

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Keep me signed in on this device')
            ->assertSee(route('admin.password.request'));

        $this->post(route('admin.login.post'), [
            'email' => $this->admin->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);
        $this->assertNotNull($this->admin->fresh()->remember_token);
    }

    public function test_active_admin_can_request_and_complete_a_password_reset(): void
    {
        auth()->logout();
        NotificationFacade::fake();

        $this->post(route('admin.password.email'), ['email' => $this->admin->email])
            ->assertSessionHas('status');

        NotificationFacade::assertSentTo($this->admin, ResetPasswordNotification::class);

        $token = Password::broker()->createToken($this->admin);
        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => $this->admin->email,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('admin.login'));

        $this->assertTrue(Hash::check('NewPassword123', $this->admin->fresh()->password));
    }

    public function test_password_recovery_does_not_disclose_unknown_accounts(): void
    {
        auth()->logout();
        NotificationFacade::fake();

        $this->post(route('admin.password.email'), ['email' => 'unknown@example.com'])
            ->assertSessionHas('status');

        NotificationFacade::assertNothingSent();
    }

    public function test_admin_can_reply_to_a_support_ticket(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-TEST-001',
            'user_id' => $customer->id,
            'subject' => 'Order question',
            'description' => 'Where is my order?',
            'category' => 'order_issue',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->post(route('admin.tickets.messages.store', $ticket), [
            'message' => 'We are checking this for you now.',
        ])->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->admin->id,
            'is_internal' => false,
        ]);
        $this->assertSame('waiting_customer', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->first_response_at);
        $this->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('We are checking this for you now.');
    }

    public function test_order_status_change_creates_a_tracking_event(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $service = Service::create([
            'code' => 'TEST-RIDE', 'name' => 'Test Ride', 'base_fare' => 50,
            'per_km_rate' => 10, 'per_minute_rate' => 2, 'minimum_fare' => 50,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'estimated_fare' => 100,
        ]);

        $this->patch(route('admin.orders.status', $order), ['status' => 'accepted'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertDatabaseHas('order_tracking_events', [
            'order_id' => $order->id,
            'event_type' => 'order_status_changed',
            'user_id' => $this->admin->id,
        ]);
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Tracking Timeline');
    }

    public function test_admin_can_generate_daily_operational_reports(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $service = Service::create([
            'code' => 'REPORT-RIDE', 'name' => 'Report Ride', 'base_fare' => 50,
            'per_km_rate' => 10, 'per_minute_rate' => 2, 'minimum_fare' => 50,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'estimated_fare' => 125,
            'actual_fare' => 120,
            'actual_distance_m' => 5000,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 120,
            'currency' => 'PHP',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $date = today()->toDateString();
        $this->post(route('admin.reports.generate'), ['report_date' => $date])
            ->assertRedirect(route('admin.reports.daily', $date));

        $this->assertDatabaseHas('daily_reports', [
            'total_orders' => 1,
            'completed_orders' => 1,
            'total_revenue' => 120,
            'total_distance_km' => 5,
        ]);
        $this->assertSame($date, DailyReport::firstOrFail()->report_date->toDateString());
        $this->assertDatabaseHas('revenue_analytics', [
            'revenue_type' => 'gross',
            'revenue_source' => 'orders',
            'amount' => 120,
        ]);
    }

    public function test_admin_can_manage_partner_menu_and_resolve_security_events(): void
    {
        $owner = User::factory()->create(['role' => 'partner', 'is_active' => true]);
        $partner = Partner::create([
            'user_id' => $owner->id,
            'name' => 'Test Kitchen',
            'slug' => 'test-kitchen',
            'status' => 'approved',
        ]);

        $this->post(route('admin.partners.menu.categories.store', $partner), [
            'name' => 'Meals', 'sort_order' => 1, 'is_active' => 1,
        ])->assertRedirect();
        $category = MenuCategory::firstOrFail();

        $this->post(route('admin.partners.menu.items.store', $partner), [
            'menu_category_id' => $category->id,
            'name' => 'Rice Bowl',
            'price' => 149.50,
            'is_available' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('menu_items', ['partner_id' => $partner->id, 'name' => 'Rice Bowl']);
        $this->get(route('admin.partners.menu.index', $partner))->assertOk()->assertSee('Rice Bowl');
        $this->get(route('admin.partners.staff.index', $partner))->assertOk();

        $event = SecurityEvent::create([
            'event_type' => 'suspicious_login',
            'severity' => 'high',
            'description' => 'Multiple failed attempts.',
            'detected_at' => now(),
        ]);
        $this->patch(route('admin.security-events.update', $event), [
            'is_resolved' => 1,
            'resolution_notes' => 'Verified with the account owner.',
        ])->assertRedirect(route('admin.security-events.show', $event));

        $this->assertTrue($event->fresh()->is_resolved);
        $this->assertSame($this->admin->email, $event->fresh()->resolved_by);
        $this->get(route('admin.security-events.show', $event))->assertOk()->assertSee('Verified with the account owner.');
    }
}
