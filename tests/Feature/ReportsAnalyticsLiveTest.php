<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAnalyticsLiveTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function paidOrder(int $amount = 500, int $daysAgo = 1): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Live Data Customer']);
        $service = Service::firstOrCreate(['code' => 'lv-live'], ['name' => 'Live', 'base_fare' => 50]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'estimated_fare' => $amount,
            'actual_fare' => $amount,
        ]);
        $order->created_at = now()->subDays($daysAgo);
        $order->saveQuietly();

        Payment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => $amount,
            'method' => 'card',
            'status' => 'paid',
            'paid_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_analytics_index_shows_live_revenue_without_generated_reports(): void
    {
        $this->paidOrder(500);
        $this->paidOrder(250, 2);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.analytics.index'));

        $response->assertOk();
        $response->assertSee('750.00');
        $response->assertSee('Total Revenue (30d)');
    }

    public function test_reports_index_shows_live_summary_without_generated_reports(): void
    {
        $this->paidOrder(500);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('500.00');
        $response->assertSee('Revenue (30d)');
    }

    public function test_reports_revenue_falls_back_to_live_payments_when_table_empty(): void
    {
        $this->paidOrder(500);
        $this->paidOrder(250, 2);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.revenue'));

        $response->assertOk();
        $response->assertSee('750.00');
        $response->assertSee('card');
    }

    public function test_report_generate_stores_daily_report(): void
    {
        $this->actingAs($this->admin());

        $date = now()->subDay()->toDateString();
        $this->post(route('admin.reports.generate'), ['report_date' => $date])
            ->assertRedirect(route('admin.reports.daily', $date));

        $this->assertNotNull(
            DailyReport::orderByDesc('report_date')->first()?->report_date->toDateString() === $date
        );
    }
}