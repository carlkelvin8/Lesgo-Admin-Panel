<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DataRetentionPolicy;
use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\FaqArticle;
use App\Models\FaqCategory;
use App\Models\MenuCategory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerStaff;
use App\Models\Payment;
use App\Models\RateLimitRule;
use App\Models\RatingReview;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminFunctionalWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
    }

    public function test_roles_page_counts_assigned_administrators_by_effective_role(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('1 administrator assigned');
    }

    public function test_wallet_adjustment_without_optional_reference_creates_a_ledger_entry(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);
        $this->post(route('admin.wallets.adjust', $wallet), [
            'type' => 'credit', 'amount' => 25, 'reason' => 'Approved support correction',
        ])->assertSessionHas('success');
        $this->assertSame('125.00', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', ['wallet_id' => $wallet->id, 'amount' => 25, 'balance_after' => 125]);
        $this->assertNotEmpty($wallet->transactions()->firstOrFail()->reference);
    }

    public function test_wallet_overdraft_is_rejected_without_changing_the_ledger(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10]);
        $this->post(route('admin.wallets.adjust', $wallet), [
            'type' => 'debit', 'amount' => 11, 'reason' => 'Approved support correction',
        ])->assertSessionHasErrors('amount');
        $this->assertSame('10.00', $wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_driver_creation_rejects_a_non_driver_account(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->post(route('admin.drivers.store'), ['user_id' => $customer->id])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('driver_profiles', 0);
    }

    public function test_driver_creation_rejects_a_second_profile_for_the_same_account(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        DriverProfile::create(['user_id' => $user->id]);
        $this->post(route('admin.drivers.store'), ['user_id' => $user->id])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('driver_profiles', 1);
    }

    public function test_driver_can_be_created_with_blank_optional_fields(): void
    {
        $user = User::factory()->create(['role' => 'driver']);
        $this->post(route('admin.drivers.store'), ['user_id' => $user->id, 'partner_id' => '', 'package_tier' => '', 'license_number' => '', 'vehicle_type' => '', 'plate_number' => ''])
            ->assertRedirect(route('admin.drivers.index'));
        $this->assertDatabaseHas('driver_profiles', ['user_id' => $user->id, 'status' => 'pending']);
        $driver = DriverProfile::firstOrFail();
        $this->put(route('admin.drivers.update', $driver), ['status' => 'active', 'package_tier' => ''])
            ->assertRedirect(route('admin.drivers.show', $driver));
        $this->assertSame('basic', $driver->fresh()->package_tier);
    }

    public function test_partner_creation_rejects_a_non_partner_account(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->post(route('admin.partners.store'), ['user_id' => $user->id, 'name' => 'Kitchen'])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('partners', 0);
    }

    public function test_partner_creation_rejects_an_already_assigned_account(): void
    {
        $user = User::factory()->create(['role' => 'partner']);
        Partner::create(['user_id' => $user->id, 'name' => 'Kitchen', 'slug' => 'kitchen']);
        $this->post(route('admin.partners.store'), ['user_id' => $user->id, 'name' => 'Second Kitchen'])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('partners', 1);
    }

    public function test_partner_can_be_created_edited_toggled_and_deleted_when_unused(): void
    {
        $user = User::factory()->create(['role' => 'partner']);
        $this->post(route('admin.partners.store'), ['user_id' => $user->id, 'name' => 'Kitchen'])->assertRedirect(route('admin.partners.index'));
        $partner = Partner::firstOrFail();
        $this->put(route('admin.partners.update', $partner), ['name' => 'Updated Kitchen', 'status' => 'approved', 'is_open' => 1, 'is_featured' => 0])->assertRedirect(route('admin.partners.show', $partner));
        $this->assertTrue((bool) $partner->fresh()->is_open);
        $this->post(route('admin.partners.toggle', $partner))->assertSessionHas('success');
        $this->assertFalse((bool) $partner->fresh()->is_open);
        $this->delete(route('admin.partners.destroy', $partner))->assertRedirect(route('admin.partners.index'));
        $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
    }

    public function test_ticket_assignment_rejects_a_customer(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $ticket = $this->ticket($user);
        $this->patch(route('admin.tickets.update', $ticket), ['status' => 'in_progress', 'priority' => 'high', 'assigned_to' => $user->id])
            ->assertSessionHasErrors('assigned_to');
        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_ticket_can_be_assigned_resolved_and_replied_to_internally(): void
    {
        $ticket = $this->ticket(User::factory()->create());
        $this->patch(route('admin.tickets.update', $ticket), ['status' => 'resolved', 'priority' => 'high', 'assigned_to' => auth()->id()])
            ->assertSessionHas('success');
        $this->assertNotNull($ticket->fresh()->resolved_at);
        $this->post(route('admin.tickets.messages.store', $ticket), ['message' => 'Internal investigation note', 'is_internal' => 1])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->first_response_at);
        $this->assertSame('resolved', $ticket->fresh()->status);
    }

    public function test_faq_category_and_article_accept_blank_optional_sort_orders(): void
    {
        $this->post(route('admin.faq.categories.store'), ['name' => 'Help', 'sort_order' => '', 'is_active' => 1])
            ->assertRedirect(route('admin.faq.categories'));
        $category = FaqCategory::firstOrFail();
        $this->post(route('admin.faq.articles.store'), ['category_id' => $category->id, 'title' => 'Help article', 'content' => 'Instructions', 'sort_order' => ''])
            ->assertRedirect(route('admin.faq.articles'));
        $this->assertSame(0, FaqArticle::firstOrFail()->sort_order);
        $article = FaqArticle::firstOrFail();
        $this->put(route('admin.faq.categories.update', $category), ['name' => 'Updated Help', 'sort_order' => '', 'is_active' => 1])->assertSessionHas('success');
        $this->put(route('admin.faq.articles.update', $article), ['category_id' => $category->id, 'title' => 'Updated article', 'content' => 'Updated instructions', 'sort_order' => '', 'is_published' => 1])->assertSessionHas('success');
        $this->assertSame(0, $article->fresh()->sort_order);
        $this->assertTrue($article->fresh()->is_published);
        $this->delete(route('admin.faq.articles.destroy', $article))->assertSessionHas('success');
        $this->delete(route('admin.faq.categories.destroy', $category))->assertSessionHas('success');
        $this->assertDatabaseMissing('faq_articles', ['id' => $article->id]);
        $this->assertDatabaseMissing('faq_categories', ['id' => $category->id]);
    }

    public function test_service_create_edit_and_toggle_persist_the_form_values(): void
    {
        $data = ['code' => 'TEST-SERVICE', 'name' => 'Test service', 'base_fare' => 50, 'per_km_rate' => 10, 'per_minute_rate' => 2, 'minimum_fare' => 60];
        $this->post(route('admin.services.store'), $data)->assertRedirect(route('admin.services.index'));
        $service = Service::firstOrFail();
        $this->put(route('admin.services.update', $service), [...$data, 'name' => 'Updated service', 'is_active' => 0])->assertRedirect(route('admin.services.show', $service));
        $this->assertFalse((bool) $service->fresh()->is_active);
        $this->post(route('admin.services.toggle', $service))->assertSessionHas('success');
        $this->assertTrue((bool) $service->fresh()->is_active);
        $this->assertSame('Updated service', $service->fresh()->name);
    }

    public function test_partner_menu_cannot_use_another_partners_category(): void
    {
        $first = Partner::create(['name' => 'First', 'slug' => 'first']);
        $second = Partner::create(['name' => 'Second', 'slug' => 'second']);
        $category = MenuCategory::create(['partner_id' => $first->id, 'name' => 'Meals']);
        $this->post(route('admin.partners.menu.items.store', $second), ['menu_category_id' => $category->id, 'name' => 'Meal', 'price' => 100])->assertNotFound();
        $this->assertDatabaseCount('menu_items', 0);
    }

    public function test_refunds_reject_overpayment_and_full_refunds_cannot_be_repeated(): void
    {
        $payment = Payment::factory()->paid()->create(['amount' => 100]);
        $data = ['amount' => 101, 'reason' => 'Approved customer refund'];
        $this->post(route('admin.payments.refund', $payment), $data)->assertSessionHasErrors('amount');
        $this->post(route('admin.payments.refund', $payment), [...$data, 'amount' => 100])->assertSessionHas('success');
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $payment->order->fresh()->payment_status);
        $this->post(route('admin.payments.refund', $payment), [...$data, 'amount' => 1])->assertSessionHasErrors('amount');
        $this->assertSame('100.00', $payment->fresh()->refunded_amount);
    }

    public function test_notification_can_be_marked_read_and_deleted(): void
    {
        $notification = Notification::create(['user_id' => auth()->id(), 'type' => 'test', 'title' => 'Test', 'body' => 'Message']);
        $this->patch(route('admin.notifications.read', $notification))->assertSessionHas('success');
        $readAt = $notification->fresh()->read_at;
        $this->assertNotNull($readAt);
        $this->patch(route('admin.notifications.read', $notification))->assertSessionHas('success');
        $this->assertTrue($readAt->equalTo($notification->fresh()->read_at));
        $this->delete(route('admin.notifications.destroy', $notification))->assertRedirect(route('admin.notifications.index'));
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_finance_role_cannot_change_orders_or_create_services(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'finance', 'is_active' => true]));
        $order = Order::factory()->create();
        $this->patch(route('admin.orders.status', $order), ['status' => 'accepted'])->assertForbidden();
        $this->post(route('admin.services.store'), [])->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    private function ticket(User $user): SupportTicket
    {
        return SupportTicket::create(['user_id' => $user->id, 'ticket_number' => 'TKT-WORKFLOW', 'subject' => 'Help', 'description' => 'Please help', 'category' => 'other']);
    }

    public function test_two_factor_recovery_codes_work_after_reloading_from_the_database_and_only_once(): void
    {
        $admin = auth()->user();
        $twoFactor = TwoFactorAuth::factory()->create(['user_id' => $admin->id, 'is_enabled' => true]);
        $code = $twoFactor->generateBackupCodes()[0];
        auth()->logout();
        $this->withSession(['2fa_pending_at' => now()->timestamp, '2fa_pending_user_id' => $admin->id])->post(route('admin.2fa.verify.post'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        auth()->logout();
        $this->withSession(['2fa_pending_at' => now()->timestamp, '2fa_pending_user_id' => $admin->id])->post(route('admin.2fa.verify.post'), ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_two_factor_challenge_rejects_an_admin_deactivated_during_login(): void
    {
        $admin = auth()->user();
        $twoFactor = TwoFactorAuth::factory()->create(['user_id' => $admin->id, 'is_enabled' => true]);
        $secret = $twoFactor->generateTotpSecret();
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $admin->update(['is_active' => false]);
        auth()->logout();
        $this->withSession(['2fa_pending_at' => now()->timestamp, '2fa_pending_user_id' => $admin->id])->post(route('admin.2fa.verify.post'), ['code' => $code])
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
        $this->assertNull(session('2fa_pending_user_id'));
    }

    public function test_password_change_does_not_put_the_current_password_in_the_audit_log(): void
    {
        $this->put(route('admin.profile.password'), ['current_password' => 'password', 'password' => 'ChangedPassword123', 'password_confirmation' => 'ChangedPassword123'])
            ->assertSessionHas('success');
        $audit = AuditLog::where('action', 'admin.profile.password')->firstOrFail();
        $this->assertArrayNotHasKey('current_password', $audit->new_values);
        $this->assertArrayNotHasKey('password', $audit->new_values);
        $this->assertArrayNotHasKey('password_confirmation', $audit->new_values);
    }

    public function test_document_rejection_requires_a_reason_and_approval_persists_the_reviewer(): void
    {
        $document = DocumentVerification::create(['user_id' => User::factory()->create()->id, 'document_type' => 'valid_id', 'document_urls' => []]);
        $this->patch(route('admin.document-verifications.update', $document), ['status' => 'rejected'])->assertSessionHasErrors('rejection_reason');
        $this->patch(route('admin.document-verifications.update', $document), ['status' => 'approved', 'admin_notes' => 'Verified details'])->assertSessionHas('success');
        $this->assertSame('approved', $document->fresh()->status);
        $this->assertSame(auth()->id(), $document->fresh()->verified_by);
        $this->assertNotNull($document->fresh()->reviewed_at);
    }

    public function test_rating_moderation_can_clear_flags_and_publish_a_business_response(): void
    {
        $order = Order::factory()->create();
        $review = RatingReview::create(['user_id' => $order->customer_id, 'order_id' => $order->id, 'overall_rating' => 4, 'is_featured' => true, 'is_public' => true]);
        $this->patch(route('admin.ratings.update', $review), ['status' => 'approved', 'business_response' => 'Thank you for your feedback.'])->assertSessionHas('success');
        $this->assertFalse($review->fresh()->is_featured);
        $this->assertFalse($review->fresh()->is_public);
        $this->assertNotNull($review->fresh()->business_responded_at);
        $this->assertSame(auth()->id(), $review->fresh()->moderated_by);
    }

    public function test_order_cancellation_requires_a_reason_and_creates_a_tracking_event(): void
    {
        $order = Order::factory()->create();
        $this->patch(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertSessionHasErrors('cancel_reason');
        $this->patch(route('admin.orders.status', $order), ['status' => 'cancelled', 'cancel_reason' => 'Customer requested cancellation'])->assertSessionHas('success');
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->cancelled_at);
        $this->assertDatabaseHas('order_tracking_events', ['order_id' => $order->id, 'event_type' => 'order_status_changed']);
    }

    public function test_administrator_can_revoke_only_their_own_other_sessions(): void
    {
        $other = User::factory()->create(['role' => 'admin']);
        foreach (['own-session' => auth()->id(), 'other-session' => $other->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => now()->timestamp]);
        }
        $this->delete(route('admin.profile.sessions.destroy', 'other-session'))->assertSessionHas('success');
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        $this->delete(route('admin.profile.sessions.destroy', 'own-session'))->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => 'own-session']);
        $currentSessionId = session()->getId();
        $this->withCookie(config('session.cookie'), $currentSessionId)
            ->delete(route('admin.profile.sessions.destroy', $currentSessionId))->assertSessionHasErrors('session');
    }

    public function test_partner_staff_can_be_added_edited_and_removed_with_ownership_checks(): void
    {
        $partner = Partner::create(['name' => 'Staff Kitchen', 'slug' => 'staff-kitchen']);
        $other = Partner::create(['name' => 'Other Kitchen', 'slug' => 'other-kitchen']);
        $user = User::factory()->create(['is_active' => true]);
        $this->post(route('admin.partners.staff.store', $partner), ['user_id' => $user->id, 'role' => 'cook', 'permissions' => ['orders']])->assertSessionHas('success');
        $staff = PartnerStaff::firstOrFail();
        $this->post(route('admin.partners.staff.store', $partner), ['user_id' => $user->id, 'role' => 'cook'])->assertSessionHasErrors('user_id');
        $this->put(route('admin.partners.staff.update', [$other, $staff]), ['role' => 'admin', 'is_active' => 1])->assertNotFound();
        $this->put(route('admin.partners.staff.update', [$partner, $staff]), ['role' => 'cashier', 'is_active' => 0])->assertSessionHas('success');
        $this->assertFalse((bool) $staff->fresh()->is_active);
        $this->assertSame([], $staff->fresh()->permissions);
        $this->delete(route('admin.partners.staff.destroy', [$partner, $staff]))->assertSessionHas('success');
        $this->assertDatabaseMissing('partner_staff', ['id' => $staff->id]);
    }

    public function test_rate_limit_and_retention_policy_forms_validate_and_save_changes(): void
    {
        $rule = RateLimitRule::create(['name' => 'QA rule', 'endpoint_pattern' => 'admin/*', 'max_attempts' => 5, 'window_minutes' => 1, 'scope' => 'user', 'priority' => 1, 'is_active' => true]);
        $this->patch(route('admin.security-settings.rate-limits.update', $rule), ['max_attempts' => 0, 'window_minutes' => 1, 'priority' => 1, 'is_active' => 1])->assertSessionHasErrors('max_attempts');
        $this->patch(route('admin.security-settings.rate-limits.update', $rule), ['max_attempts' => 10, 'window_minutes' => 5, 'priority' => 20, 'is_active' => 0])->assertSessionHas('success');
        $this->assertSame(10, $rule->fresh()->max_attempts);
        $policy = DataRetentionPolicy::create(['data_type' => 'qa_policy', 'category' => 'security', 'retention_days' => 30, 'deletion_method' => 'soft_delete', 'is_active' => true]);
        $this->patch(route('admin.security-settings.retention.update', $policy), ['retention_days' => 0, 'deletion_method' => 'hard_delete', 'is_active' => 1])->assertSessionHasErrors('retention_days');
        $this->patch(route('admin.security-settings.retention.update', $policy), ['retention_days' => 90, 'deletion_method' => 'anonymize', 'is_active' => 0])->assertSessionHas('success');
        $this->assertSame(90, $policy->fresh()->retention_days);
        $this->assertSame('anonymize', $policy->fresh()->deletion_method);
    }
}
