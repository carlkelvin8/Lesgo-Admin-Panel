<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_update_role_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $support = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'support',
            'is_active' => true,
        ]);
        $supportRole = AdminRole::query()->findOrFail('support');

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Roles &amp; Permissions', false)
            ->assertSee('Support');

        $this->get(route('admin.roles.edit', $supportRole))
            ->assertOk()
            ->assertSee('Support Permissions')
            ->assertSee('Save permissions')
            ->assertSee('View payments');

        $this->put(route('admin.roles.update', $supportRole), [
            'permissions' => ['payments.view'],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame(
            ['dashboard.view', 'payments.view'],
            $supportRole->fresh()->permissions,
        );

        $this->actingAs($support);
        $this->get(route('admin.payments.index'))->assertOk();
        $this->get(route('admin.tickets.index'))->assertForbidden();
    }

    public function test_role_without_management_access_cannot_open_role_permissions(): void
    {
        $operations = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'operations',
            'is_active' => true,
        ]);

        $this->actingAs($operations)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_six_selected_permissions_remain_six_after_viewing_roles(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $operations = AdminRole::query()->findOrFail('operations');
        $selected = [
            'dashboard.view', 'users.view', 'partners.view',
            'orders.view', 'tickets.manage', 'reports.view',
        ];

        $this->actingAs($superAdmin)
            ->put(route('admin.roles.update', $operations), ['permissions' => $selected])
            ->assertRedirect(route('admin.roles.index'));

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('6 of 24 permissions');
        $this->get(route('admin.roles.edit', $operations))
            ->assertOk()
            ->assertSee('6</strong> of 24 permissions selected', false);
        $this->assertSame($selected, $operations->fresh()->permissions);
    }

    public function test_required_only_role_is_not_reset_to_defaults_on_page_view(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $operations = AdminRole::query()->findOrFail('operations');
        $operations->update(['permissions' => ['dashboard.view']]);

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('1 of 24 permissions');
        $this->get(route('admin.roles.edit', $operations))->assertOk();

        $this->assertSame(['dashboard.view'], $operations->fresh()->permissions);
    }

    public function test_role_count_reflects_a_change_made_by_another_worker(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $operations = AdminRole::query()->findOrFail('operations');
        $operations->update(['permissions' => ['dashboard.view']]);

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.index'))
            ->assertSee('1 of 24 permissions');

        // A separate worker writes directly to the shared database.
        DB::table('admin_access_roles')->where('key', 'operations')->update([
            'permissions' => json_encode(['dashboard.view', 'users.view', 'orders.view']),
        ]);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('3 of 24 permissions');
        $this->get(route('admin.roles.edit', $operations))
            ->assertOk()
            ->assertSee('3</strong> of 24 permissions selected', false);
    }

    public function test_saving_three_permissions_from_required_only_updates_the_count(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $operations = AdminRole::query()->findOrFail('operations');
        $operations->update(['permissions' => ['dashboard.view']]);

        $this->actingAs($superAdmin)
            ->put(route('admin.roles.update', $operations), [
                'permissions' => ['dashboard.view', 'users.view', 'orders.view'],
                'select_all_flag' => '0',
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('3 of 24 permissions');
        $this->assertSame(
            ['dashboard.view', 'users.view', 'orders.view'],
            $operations->fresh()->permissions,
        );
    }

    public function test_super_admin_permissions_cannot_be_changed(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $superAdminRole = AdminRole::query()->findOrFail('super_admin');

        $this->actingAs($superAdmin)
            ->get(route('admin.roles.edit', $superAdminRole))
            ->assertOk()
            ->assertSee('Super Admin always has full access')
            ->assertDontSee('Save permissions');

        $this->put(route('admin.roles.update', $superAdminRole), [
            'permissions' => ['dashboard.view'],
        ])
            ->assertForbidden();

        $this->assertSame(['*'], $superAdminRole->fresh()->permissions);
    }

    public function test_admin_role_migration_can_reconcile_an_existing_table(): void
    {
        $migration = require database_path('migrations/2026_08_20_000002_create_admin_access_roles_table.php');

        $migration->up();

        $this->assertDatabaseCount('admin_access_roles', 4);
        $this->assertDatabaseHas('admin_access_roles', [
            'key' => 'super_admin',
            'is_protected' => true,
        ]);
    }
}
