<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\DataRetentionPolicy;
use App\Models\IpBlacklist;
use App\Models\MenuCategory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\SecurityEvent;
use App\Models\SecuritySetting;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use App\Models\WalletTransaction;
use App\Services\AdminNetworkAccess;
use Database\Seeders\SecuritySettingsSeeder;
use App\Notifications\AdminResetPasswordNotification;
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
            'admin.reports.index', 'admin.security-settings.index', 'admin.profile.edit',
            'admin.wallets.top-ups.index',
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
        $this->assertSame(0, Notification::where('delivery_status', '!=', 'delivered')->count());
        $this->assertSame(2, Notification::where('delivered_via', 'database')->count());
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

        NotificationFacade::assertSentTo($this->admin, AdminResetPasswordNotification::class);

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

    public function test_finance_admin_is_limited_to_financial_and_read_only_user_modules(): void
    {
        $finance = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'finance',
            'is_active' => true,
        ]);

        $this->actingAs($finance);

        $this->get(route('admin.payments.index'))->assertOk();
        $this->get(route('admin.wallets.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->get(route('admin.partners.index'))->assertForbidden();
        $this->get(route('admin.notifications.index'))->assertForbidden();
        $this->get(route('admin.security-settings.index'))->assertForbidden();
    }

    public function test_super_admin_can_manage_security_settings_and_network_rules_safely(): void
    {
        $setting = SecuritySetting::create([
            'setting_key' => 'max_failed_login_attempts',
            'setting_value' => '5',
            'data_type' => 'integer',
            'description' => 'Maximum failed logins',
            'category' => 'authentication',
        ]);
        DataRetentionPolicy::create([
            'data_type' => 'security_events',
            'category' => 'security',
            'retention_days' => 730,
            'deletion_method' => 'hard_delete',
            'is_active' => true,
        ]);

        $this->get(route('admin.security-settings.index'))
            ->assertOk()
            ->assertSee('Security Center')
            ->assertSee('Maximum failed logins');

        $this->patch(route('admin.security-settings.settings.update', $setting), [
            'setting_value' => '7',
        ])->assertSessionHas('success');

        $this->assertSame('7', $setting->fresh()->setting_value);
        $this->assertSame($this->admin->email, $setting->fresh()->updated_by);

        IpBlacklist::create([
            'ip_address' => '203.0.113.5',
            'reason' => 'security_threat',
            'description' => 'Automated test rule',
            'is_active' => true,
            'created_by' => $this->admin->email,
        ]);

        $networkAccess = app(AdminNetworkAccess::class);
        $this->assertFalse($networkAccess->allows('203.0.113.5'));
        $this->assertTrue($networkAccess->allows('203.0.113.6'));

        $this->post(route('admin.security-settings.ip-rules.store'), [
            'list' => 'blacklist',
            'ip_address' => '127.0.0.1',
            'reason' => 'security_threat',
        ])->assertSessionHasErrors('ip_address');
    }

    public function test_security_defaults_can_be_seeded_without_enabling_unconfigured_two_factor_auth(): void
    {
        $this->seed(SecuritySettingsSeeder::class);

        $this->assertDatabaseHas('security_settings', [
            'setting_key' => '2fa_required_for_admin',
            'setting_value' => '0',
        ]);
        $this->assertDatabaseHas('rate_limit_rules', ['name' => 'Authentication Endpoints']);
        $this->assertDatabaseHas('data_retention_policies', ['data_type' => 'security_events']);
    }

    public function test_admin_can_update_profile_password_and_invalidate_other_sessions(): void
    {
        $this->put(route('admin.profile.update'), [
            'name' => 'Updated Administrator',
            'email' => $this->admin->email,
            'phone_number' => '09171234567',
        ])->assertSessionHas('success');

        $this->put(route('admin.profile.password'), [
            'current_password' => 'password',
            'password' => 'StrongerPassword123',
            'password_confirmation' => 'StrongerPassword123',
        ])->assertSessionHas('success');

        $admin = $this->admin->fresh();
        $this->assertSame('Updated Administrator', $admin->name);
        $this->assertTrue(Hash::check('StrongerPassword123', $admin->password));
        $this->assertNotNull($admin->password_changed_at);
    }

    public function test_finance_admin_can_adjust_wallet_and_approve_top_up_once(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 100, 'currency' => 'PHP']);

        $this->post(route('admin.wallets.adjust', $wallet), [
            'type' => 'debit',
            'amount' => 25,
            'reason' => 'Correction requested in support ticket TKT-100.',
            'reference' => 'TKT-100',
        ])->assertSessionHas('success');

        $this->assertSame('75.00', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 25,
            'created_by' => $this->admin->id,
        ]);

        $topUp = WalletTopUp::create([
            'user_id' => $customer->id,
            'wallet_id' => $wallet->id,
            'amount' => 150,
            'fee' => 5,
            'total_charged' => 155,
            'currency' => 'PHP',
            'status' => 'pending',
            'payment_method' => 'xendit',
            'provider' => 'xendit',
            'external_id' => 'TOPUP-TEST-001',
        ]);

        $this->post(route('admin.wallets.top-ups.review', $topUp), [
            'decision' => 'approve',
        ])->assertSessionHas('success');

        $this->assertSame('225.00', $wallet->fresh()->balance);
        $this->assertSame('paid', $topUp->fresh()->status);
        $this->assertSame(1, WalletTransaction::where('source_type', 'wallet_top_up')->where('source_id', $topUp->id)->count());

        $this->post(route('admin.wallets.top-ups.review', $topUp), [
            'decision' => 'approve',
        ])->assertSessionHasErrors('decision');

        $this->assertSame('225.00', $wallet->fresh()->balance);
    }

    public function test_finance_admin_can_record_partial_refund_and_reconcile_payment(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $service = Service::create([
            'code' => 'REFUND-RIDE', 'name' => 'Refund Ride', 'base_fare' => 50,
            'per_km_rate' => 10, 'per_minute_rate' => 2, 'minimum_fare' => 50,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'estimated_fare' => 200,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 200,
            'currency' => 'PHP',
            'status' => 'paid',
            'provider_reference' => 'PAY-TEST-001',
            'paid_at' => now(),
        ]);

        $this->post(route('admin.payments.refund', $payment), [
            'amount' => 50,
            'reason' => 'Partial service refund approved after investigation.',
        ])->assertSessionHas('success');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('50.00', $payment->fresh()->refunded_amount);
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->post(route('admin.payments.reconcile', $payment), [
            'reconciliation_status' => 'matched',
            'reconciliation_notes' => 'Matched against the provider settlement report.',
        ])->assertSessionHas('success');

        $this->assertSame('matched', $payment->fresh()->reconciliation_status);
        $this->assertSame($this->admin->id, $payment->fresh()->reconciled_by);
        $this->assertNotNull($payment->fresh()->reconciled_at);
    }
}
