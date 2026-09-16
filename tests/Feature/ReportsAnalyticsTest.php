<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAnalyticsTest extends TestCase
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

    public function test_analytics_index_loads(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.analytics.index'));
        $response->assertOk();
    }

    public function test_reports_index_loads(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.reports.index'));
        $response->assertOk();
    }

    public function test_reports_revenue_loads(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get(route('admin.reports.revenue'));
        $response->assertOk();
    }
}