<?php

namespace Tests\Feature;

use App\Models\DailyMetric;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RevenueAnalytics;
use App\Models\SecurityEvent;
use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
    }

    public function test_dashboard_renders_chart_titles_and_the_real_order_fare_in_pesos(): void
    {
        Order::factory()->create(['estimated_fare' => 321.50, 'actual_fare' => null]);
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Revenue Overview')->assertSee('Order Status Distribution')
            ->assertSee('User Growth')->assertSee('₱321.50');
    }

    public function test_stat_cards_render_usable_links_icons_and_currency(): void
    {
        $html = Blade::render('<x-stat-card label="Revenue" value="125" format="currency" icon="coins" href="/admin/payments" aria-label="Payments" />');
        $this->assertStringContainsString('href="/admin/payments"', $html);
        $this->assertStringContainsString('aria-label="Payments"', $html);
        $this->assertStringContainsString('fa-coins', $html);
        $this->assertStringContainsString('₱125.00', $html);
    }

    public function test_dashboard_stats_refresh_after_an_administrator_creates_a_user(): void
    {
        $before = app(DashboardService::class)->getStats()['total_users'];
        $this->post(route('admin.users.store'), ['name' => 'New account', 'email' => 'created@example.test', 'role' => 'customer', 'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123'])->assertSessionHas('success');
        $this->assertSame($before + 1, app(DashboardService::class)->getStats()['total_users']);
    }

    public function test_notification_badge_and_preview_refresh_after_reading_and_deleting(): void
    {
        $notice = Notification::create(['user_id' => auth()->id(), 'type' => 'test', 'title' => 'Unique sidebar notice', 'body' => 'Notice']);
        $this->get(route('admin.profile.edit'))->assertSee('Unique sidebar notice');
        $this->patch(route('admin.notifications.read', $notice))->assertSessionHas('success');
        $this->get(route('admin.profile.edit'))->assertOk();
        $this->assertSame(0, Cache::get('admin:notification_unread_count:v2'));
        $this->delete(route('admin.notifications.destroy', $notice))->assertSessionHas('success');
        $this->get(route('admin.profile.edit'))->assertDontSee('Unique sidebar notice');
    }

    public function test_analytics_includes_todays_generated_metrics(): void
    {
        DailyMetric::create(['date' => today(), 'metric_type' => 'operations', 'metric_category' => 'daily', 'metric_key' => 'total_orders', 'metric_value' => 42]);
        $this->get(route('admin.analytics.index'))->assertOk()
            ->assertViewHas('todayMetrics', fn ($metrics) => $metrics->flatten()->count() === 1);
    }

    public function test_revenue_average_is_weighted_by_transaction_count(): void
    {
        foreach ([[100, 1], [200, 10]] as $index => [$amount, $count]) {
            RevenueAnalytics::create(['date' => today()->subDays($index), 'revenue_type' => 'gross', 'revenue_source' => 'orders', 'amount' => $amount, 'transaction_count' => $count, 'average_transaction_value' => $amount / $count, 'currency' => 'PHP']);
        }
        $this->get(route('admin.reports.revenue'))->assertOk()
            ->assertViewHas('summary', fn ($summary) => abs((float) $summary->avg_transaction - 300 / 11) < 0.001)
            ->assertViewHas('bySource', fn ($sources) => abs((float) $sources->first()->avg_transaction - 300 / 11) < 0.001);
    }

    public function test_dashboard_seven_day_period_contains_exactly_seven_calendar_days(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        Payment::factory()->paid()->create(['amount' => 100, 'created_at' => today()->subDays(7)]);
        Payment::factory()->paid()->create(['amount' => 25, 'created_at' => today()->subDays(6)]);
        $this->assertEquals(25, app(DashboardService::class)->getDailyRevenue(7)->sum('total'));
    }

    public function test_dashboard_distribution_includes_arrived_and_picked_up_orders(): void
    {
        Order::factory()->create(['status' => 'driver_arrived']);
        Order::factory()->create(['status' => 'picked_up']);
        $this->assertSame(2, (int) app(DashboardService::class)->getOrderStatusDistribution()->sum('total'));
    }

    public function test_text_search_does_not_compare_a_postgres_numeric_id_to_a_name(): void
    {
        $customer = User::factory()->create(['name' => 'Search customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        Payment::factory()->create(['customer_id' => $customer->id, 'order_id' => $order->id]);
        DB::enableQueryLog();
        foreach (['admin.orders.index', 'admin.orders.export', 'admin.payments.index', 'admin.payments.export'] as $name) {
            DB::flushQueryLog();
            $response = $this->get(route($name, ['search' => 'Search customer']));
            $response->assertOk();
            if (str_ends_with($name, '.export')) {
                $this->assertStringContainsString('Search customer', $response->streamedContent());
            }
            foreach (DB::getQueryLog() as $query) {
                if (str_contains($query['query'], 'from "orders"') || str_contains($query['query'], 'from "payments"')) {
                    $this->assertStringNotContainsString('"id" = ?', $query['query'], $name.' must not bind a name to a bigint ID');
                }
            }
        }
        DB::disableQueryLog();
    }

    public function test_password_change_rejects_the_current_password_before_any_history_exists(): void
    {
        $admin = auth()->user();
        $admin->update(['password' => 'InitialPassword123']);
        $this->put(route('admin.profile.password'), ['current_password' => 'InitialPassword123', 'password' => 'InitialPassword123', 'password_confirmation' => 'InitialPassword123'])
            ->assertSessionHasErrors('password');
        $this->assertNull(session()->getOldInput('current_password'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_password_change_cannot_reuse_the_initial_password_after_rotation(): void
    {
        auth()->user()->update(['password' => 'InitialPassword123']);
        $this->put(route('admin.profile.password'), ['current_password' => 'InitialPassword123', 'password' => 'SecondPassword123', 'password_confirmation' => 'SecondPassword123'])->assertSessionHas('success');
        $this->put(route('admin.profile.password'), ['current_password' => 'SecondPassword123', 'password' => 'InitialPassword123', 'password_confirmation' => 'InitialPassword123'])->assertSessionHasErrors('password');
        $this->assertNull(session()->getOldInput('current_password'));
    }

    public function test_two_factor_login_preserves_remember_me_after_the_challenge(): void
    {
        $admin = auth()->user();
        $twoFactor = TwoFactorAuth::factory()->create(['user_id' => $admin->id, 'is_enabled' => true]);
        $secret = $twoFactor->generateTotpSecret();
        auth()->logout();
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password', 'remember' => 1])->assertRedirect(route('admin.2fa.verify'));
        $this->assertGuest();
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post(route('admin.2fa.verify.post'), ['code' => $code])->assertRedirect(route('admin.dashboard'))
            ->assertCookie(auth()->guard()->getRecallerName());
        $this->assertTrue(auth()->guard()->getCookieJar()->queued(auth()->guard()->getRecallerName())->getExpiresTime() > time());
    }

    public function test_two_factor_challenges_expire_and_do_not_accept_codes_indefinitely(): void
    {
        $admin = auth()->user();
        $twoFactor = TwoFactorAuth::factory()->create(['user_id' => $admin->id, 'is_enabled' => true]);
        $secret = $twoFactor->generateTotpSecret();
        auth()->logout();
        $this->withSession(['2fa_pending_user_id' => $admin->id, '2fa_pending_at' => now()->subMinutes(10)->timestamp])
            ->post(route('admin.2fa.verify.post'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_two_factor_setup_does_not_send_the_authenticator_secret_to_a_third_party(): void
    {
        $this->get(route('admin.2fa.setup'))->assertOk()->assertDontSee('api.qrserver.com');
    }

    public function test_literal_search_handles_percent_underscores_backslashes_and_exclamation_marks(): void
    {
        $literal = User::factory()->create(['name' => 'Buyer_100%\\!Demo']);
        User::factory()->create(['name' => 'BuyerX100YDemo']);
        foreach (['100%', 'Buyer_', '\\!Demo'] as $search) {
            $this->get(route('admin.users.index', ['search' => $search]))->assertOk()
                ->assertViewHas('users', fn ($users) => $users->total() === 1 && $users->first()->id === $literal->id);
            $export = $this->get(route('admin.users.export', ['search' => $search]))->streamedContent();
            $this->assertStringContainsString($literal->email, $export);
            $this->assertStringNotContainsString('BuyerX100YDemo', $export);
        }
    }

    public function test_enabled_two_factor_profile_submits_regeneration_to_the_correct_endpoint(): void
    {
        TwoFactorAuth::factory()->create(['user_id' => auth()->id(), 'is_enabled' => true]);
        $this->get(route('admin.profile.edit'))->assertOk()
            ->assertSee('action="'.route('admin.2fa.regenerate').'"', false)
            ->assertSee('formaction="'.route('admin.2fa.disable').'"', false);
    }

    public function test_user_confirmation_forms_preserve_methods_and_quoted_names(): void
    {
        $customer = User::factory()->create(['name' => "O'Neil \"Test\"\nSecond line"]);
        $html = $this->get(route('admin.users.show', $customer))->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $deleteForm = $xpath->query('//form[@action="'.route('admin.users.destroy', $customer).'"][input[@name="_method" and @value="DELETE"]]');
        $this->assertSame(1, $deleteForm->length);
        $this->assertStringNotContainsString('form.innerHTML', $html);
    }

    public function test_dashboard_and_command_palette_hide_navigation_the_role_cannot_open(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'finance', 'is_active' => true]));
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('href="'.route('admin.document-verifications.index').'"', false)
            ->assertDontSee('href="'.route('admin.security-events.index').'"', false)
            ->assertDontSee("url: '".route('admin.security-settings.index')."'", false)
            ->assertDontSee("url: '".route('admin.partners.index')."'", false);
    }

    public function test_security_event_pagination_has_a_stable_order_for_identical_detection_times(): void
    {
        $expected = [];
        $detectedAt = now()->startOfSecond();
        for ($index = 0; $index < 41; $index++) {
            $expected[] = SecurityEvent::create(['event_type' => 'same_time', 'severity' => 'high', 'detected_at' => $detectedAt])->id;
        }
        $actual = [];
        for ($page = 1; $page <= 3; $page++) {
            $events = $this->get(route('admin.security-events.index', ['page' => $page]))->assertOk()->viewData('events');
            $actual = [...$actual, ...$events->pluck('id')->all()];
        }
        $this->assertSame(array_reverse($expected), $actual);
        $this->assertCount(41, array_unique($actual));
    }
}
