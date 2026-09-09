<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionSidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_admin_can_see_mission_management_and_rewards_links(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'operations',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.mission-templates.index'), false)
            ->assertSee('Missions')
            ->assertSee(route('admin.mission-rewards.index'), false)
            ->assertSee('Mission Rewards');
    }

    public function test_migration_restores_missions_permission_for_an_existing_operations_role(): void
    {
        AdminRole::query()->whereKey('operations')->update([
            'permissions' => [],
        ]);
        AdminRole::query()->whereKey('support')->update([
            'permissions' => ['tickets.manage'],
        ]);

        $migration = require database_path('migrations/2026_09_09_000005_add_missions_permission_to_operations_role.php');
        $migration->up();

        $this->assertSame(
            ['dashboard.view', 'missions.manage'],
            AdminRole::query()->findOrFail('operations')->permissions,
        );
        $this->assertSame(
            ['tickets.manage', 'dashboard.view'],
            AdminRole::query()->findOrFail('support')->permissions,
        );
    }
}
