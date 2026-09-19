<?php

namespace Tests\Feature;

use App\Models\LesbuyItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Service;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTopPartnersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($this->admin);
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        Partner::factory()->create();
        Order::factory()->completed()->count(2)->create();

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_top_partners_are_resolved_through_menus_when_order_partner_id_is_null(): void
    {
        $partner = Partner::factory()->create(['name' => 'Top Eatery']);
        $category = MenuCategory::create([
            'partner_id' => $partner->id,
            'name' => 'Rice Meals',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $menuItem = MenuItem::create([
            'partner_id' => $partner->id,
            'menu_category_id' => $category->id,
            'name' => 'Tapsilog',
            'price' => 120,
            'is_available' => true,
        ]);

        $service = Service::factory()->create(['code' => 'LESBUY']);
        $customer = User::factory()->create(['role' => 'customer']);

        foreach ([['actual_fare' => 100, 'created_at' => now()], ['actual_fare' => 50, 'created_at' => now()->subDay()]] as $state) {
            $order = Order::factory()->completed()->create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'partner_id' => null,
                ...$state,
            ]);

            LesbuyItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'name' => 'Tapsilog',
                'quantity' => 1,
                'status' => 'delivered',
            ]);
        }

        $topPartners = app(DashboardService::class)->getTopPartners(7, 5);

        $this->assertCount(1, $topPartners);
        $entry = $topPartners[0];
        $this->assertSame('Top Eatery', data_get($entry, 'partner.name'));
        $this->assertSame(2, (int) data_get($entry, 'order_count'));
        $this->assertSame(150.0, (float) data_get($entry, 'revenue'));
    }

    public function test_dashboard_page_renders_top_partners_table(): void
    {
        $partner = Partner::factory()->create(['name' => 'Render Eatery']);
        $category = MenuCategory::create([
            'partner_id' => $partner->id,
            'name' => 'Rice Meals',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $menuItem = MenuItem::create([
            'partner_id' => $partner->id,
            'menu_category_id' => $category->id,
            'name' => 'Tapsilog',
            'price' => 120,
            'is_available' => true,
        ]);

        $service = Service::factory()->create(['code' => 'LESBUY']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->completed()->create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'partner_id' => null,
            'actual_fare' => 100,
        ]);
        LesbuyItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'name' => 'Tapsilog',
            'quantity' => 1,
            'status' => 'delivered',
        ]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Render Eatery')
            ->assertSee('₱100.00');
    }
}