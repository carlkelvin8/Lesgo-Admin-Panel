<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\FaqArticle;
use App\Models\FaqCategory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\RatingReview;
use App\Models\SecurityEvent;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPageCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cards_render_usable_links_and_forward_attributes(): void
    {
        $html = Blade::render('<x-card href="/admin/users" class="test-card" aria-label="Users">Users</x-card>');
        $this->assertStringContainsString('href="/admin/users"', $html);
        $this->assertStringContainsString('test-card', $html);
        $this->assertStringContainsString('aria-label="Users"', $html);
    }

    public function test_daily_report_can_be_regenerated_and_opened(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);
        $date = today()->toDateString();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->post(route('admin.reports.generate'), ['report_date' => $date])
                ->assertRedirect(route('admin.reports.daily', $date));
        }
        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertDatabaseCount('daily_metrics', 6);
        $this->assertDatabaseCount('revenue_analytics', 1);
        $this->get(route('admin.reports.daily', $date))->assertOk()
            ->assertViewHas('metrics', fn ($metrics) => $metrics->flatten()->count() === 6)
            ->assertViewHas('revenueDetails', fn ($revenue) => $revenue->count() === 1);
        $this->get(route('admin.reports.index', ['date_from' => $date, 'date_to' => $date]))
            ->assertOk()->assertViewHas('reports', fn ($reports) => $reports->total() === 1);
    }

    public function test_public_authentication_pages_render(): void
    {
        $this->get(route('admin.login'))->assertOk();
        $this->get(route('admin.password.request'))->assertOk();
        $this->get(route('admin.password.reset', ['token' => 'test-token']))->assertOk();
        $this->get(route('admin.2fa.verify'))->assertRedirect(route('admin.login'));
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->withSession(['2fa_pending_at' => now()->timestamp, '2fa_pending_user_id' => $admin->id])->get(route('admin.2fa.verify'))->assertOk();
    }

    public function test_local_vite_assets_are_allowed_by_the_content_security_policy(): void
    {
        $hotFile = tempnam(sys_get_temp_dir(), 'admin-vite-');
        file_put_contents($hotFile, 'http://127.0.0.1:5173');
        $vite = app(Vite::class);
        $originalHotFile = $vite->hotFile();
        $originalEnv = app()->environment();
        try {
            $vite->useHotFile($hotFile);
            app()['env'] = 'local';
            $policy = $this->get(route('admin.login'))->headers->get('Content-Security-Policy');
            $this->assertStringContainsString('http://127.0.0.1:5173', $policy);
            $this->assertStringContainsString('ws://127.0.0.1:5173', $policy);
            $this->assertStringContainsString("'unsafe-eval'", $policy);

            app()['env'] = 'production';
            $policy = $this->get(route('admin.login'))->headers->get('Content-Security-Policy');
            $this->assertStringNotContainsString('http://127.0.0.1:5173', $policy);
            $this->assertStringNotContainsString('ws://127.0.0.1:5173', $policy);
        } finally {
            app()['env'] = $originalEnv;
            $vite->useHotFile($originalHotFile);
            unlink($hotFile);
        }
    }

    public function test_article_edit_preserves_its_inactive_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $category = FaqCategory::create(['name' => 'Archived help', 'slug' => 'archived-help', 'is_active' => false]);
        $article = FaqArticle::create(['category_id' => $category->id, 'title' => 'Existing article', 'slug' => 'existing', 'content' => 'Help', 'created_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.faq.articles.edit', $article))
            ->assertOk()->assertSee('Archived help');
    }

    public function test_every_admin_get_route_renders_with_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin);
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $order = Order::factory()->create(['customer_id' => $user->id, 'service_id' => $service->id]);
        $category = FaqCategory::create(['name' => 'Help', 'slug' => 'help', 'is_active' => true]);
        $parameters = [
            'user' => $user,
            'service' => $service,
            'order' => $order,
            'driver' => DriverProfile::create(['user_id' => User::factory()->create(['role' => 'driver'])->id, 'status' => 'approved']),
            'partner' => Partner::create(['user_id' => $user->id, 'name' => 'Kitchen', 'slug' => 'kitchen', 'status' => 'approved']),
            'payment' => Payment::factory()->create(['order_id' => $order->id, 'customer_id' => $user->id]),
            'wallet' => Wallet::factory()->create(['user_id' => $user->id]),
            'category' => $category,
            'article' => FaqArticle::create(['category_id' => $category->id, 'title' => 'Help article', 'slug' => 'help-article', 'content' => 'Instructions', 'created_by' => $admin->id]),
            'ticket' => SupportTicket::create(['user_id' => $user->id, 'ticket_number' => 'TKT-PAGES', 'subject' => 'Help', 'description' => 'Please help', 'category' => 'other']),
            'review' => RatingReview::create(['user_id' => $user->id, 'order_id' => $order->id, 'overall_rating' => 5]),
            'notification' => Notification::create(['user_id' => $user->id, 'type' => 'test', 'title' => 'Notice', 'body' => 'Message']),
            'documentVerification' => DocumentVerification::create(['user_id' => $user->id, 'document_type' => 'valid_id', 'document_urls' => []]),
            'securityEvent' => SecurityEvent::create(['event_type' => 'test', 'severity' => 'high']),
            'auditLog' => AuditLog::create(['user_id' => $admin->id, 'action' => 'test', 'occurred_at' => now()]),
            'adminRole' => AdminRole::findOrFail('support'),
            'date' => today()->toDateString(),
            'token' => 'test-token',
        ];
        DailyReport::create(['report_date' => $parameters['date']]);

        $failures = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods()) || ! str_starts_with($route->getName() ?? '', 'admin.')) {
                continue;
            }
            $name = $route->getName();
            if (in_array($name, ['admin.login', 'admin.password.request', 'admin.password.reset', 'admin.2fa.verify'])) {
                continue;
            }
            // Dataset-key routes have separate authorization, schema, pagination and export coverage.
            if (in_array($name, ['admin.data.index', 'admin.data.show', 'admin.data.export'])) {
                continue;
            }
            $args = array_intersect_key($parameters, array_flip($route->parameterNames()));
            $response = $this->get(route($name, $args));
            if ($response->getStatusCode() !== 200) {
                $failures[] = $name.': '.$response->getStatusCode().' '.($response->exception?->getMessage() ?? '');
            }
            if (str_ends_with($name, '.export')) {
                $response->assertStreamed();
                $this->assertNotEmpty($response->streamedContent());
            }
            if (str_ends_with($name, '.index')) {
                $this->get(route($name, [...$args, 'search' => 'Kitchen', 'date_from' => today()->subDays(7)->toDateString(), 'date_to' => today()->toDateString(), 'page' => 2]))->assertOk();
            }
        }
        $this->assertSame([], $failures);
    }
}
