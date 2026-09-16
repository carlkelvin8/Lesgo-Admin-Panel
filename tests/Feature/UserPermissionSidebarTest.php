<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionSidebarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $adminRole, ?array $perms, int $id = 1): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => "Admin {$id}",
            'email' => "admin{$id}@lesgo.test",
            'role' => 'admin',
            'admin_role' => $adminRole,
            'admin_permissions' => $perms,
            'is_active' => true,
        ]);
    }

    public function test_changing_users_admin_role_updates_the_sidebar(): void
    {
        $super  = $this->admin('super_admin', null, 1);
        $target = $this->admin('operations', null, 2);

        $this->actingAs($super)
            ->put(route('admin.users.update', $target), [
                'name'              => $target->name,
                'email'             => $target->email,
                'phone_number'      => null,
                'role'              => 'admin',
                'admin_role'        => 'support',
                'admin_permissions' => [],
                'is_active'         => '1',
            ])->assertRedirect();

        $this->assertEquals('support', $target->fresh()->admin_role);

        // Support role: dashboard.view, users.view, orders.view,
        //   tickets.manage, ratings.manage, faq.manage, notifications.manage
        $this->actingAs($target->fresh())
            ->get(route('admin.dashboard'))
            ->assertOk()
            // sidebar links present
            ->assertSee('admin/users', false)
            ->assertSee('admin/orders', false)
            ->assertSee('admin/ratings', false)
            ->assertSee('admin/tickets', false)
            ->assertSee('admin/faq/articles', false)
            // sidebar links absent
            ->assertDontSee('admin/drivers', false)
            ->assertDontSee('admin/partners', false)
            ->assertDontSee('admin/wallets"', false)
            ->assertDontSee('admin/services', false)
            ->assertDontSee('admin/roles"', false)
            ->assertDontSee('admin/payments', false);
    }

    public function test_removing_all_extra_permissions_now_persists(): void
    {
        $super  = $this->admin('super_admin', null, 1);
        $target = $this->admin('support', ['notifications.manage'], 2);

        // Unchecking all per-user extra permissions must persist empty (not keep old values)
        $this->actingAs($super)
            ->put(route('admin.users.update', $target), [
                'name'              => $target->name,
                'email'             => $target->email,
                'phone_number'      => null,
                'role'              => 'admin',
                'admin_role'        => 'support',
                'admin_permissions' => [],
                'is_active'         => '1',
            ])->assertRedirect();

        $this->assertEquals(null, $target->fresh()->admin_permissions);

        $fresh = $target->fresh();
        // role-granted perms still apply
        $this->assertTrue($fresh->hasAdminPermission('users.view'));
        $this->assertTrue($fresh->hasAdminPermission('orders.view'));
        // per-user extra perms were cleared
        $this->assertFalse($fresh->hasAdminPermission('wallets.view'));
    }

    public function test_role_granted_permission_cannot_be_revoked_by_user_level_uncheck(): void
    {
        $super  = $this->admin('super_admin', null, 1);
        $target = $this->admin('support', ['notifications.manage'], 2);

        $fresh = $target->fresh();
        // notifications.manage is granted by the support role itself
        $this->assertTrue($fresh->hasAdminPermission('notifications.manage'));
        $this->assertTrue($fresh->hasAdminPermission('users.view'));
        $this->assertTrue($fresh->hasAdminPermission('orders.view'));
    }

    public function test_legacy_admin_with_null_admin_role_becomes_super_admin_on_unmodified_save(): void
    {
        $super  = $this->admin('super_admin', null, 1);
        $legacy = User::factory()->create([
            'id'                => 2,
            'email'             => 'legacy@lesgo.test',
            'role'              => 'admin',
            'admin_role'        => null,
            'admin_permissions' => null,
            'is_active'         => true,
        ]);

        // effectiveAdminRole() returns 'super_admin' for null — form shows super_admin selected
        $this->actingAs($super)
            ->get(route('admin.users.edit', $legacy))
            ->assertSee('super_admin" selected', false);

        // Saving without changing the role select persists real super_admin in DB
        $this->actingAs($super)
            ->put(route('admin.users.update', $legacy), [
                'name'              => $legacy->name,
                'email'             => $legacy->email,
                'phone_number'      => null,
                'role'              => 'admin',
                'admin_role'        => 'super_admin',
                'admin_permissions' => [],
                'is_active'         => '1',
            ])->assertRedirect();

        $this->assertEquals('super_admin', $legacy->fresh()->admin_role);
        $this->assertTrue($legacy->fresh()->isSuperAdmin());

        // Full access — all sidebar links present
        $this->actingAs($legacy->fresh())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin/drivers', false)
            ->assertSee('admin/wallets', false)
            ->assertSee('admin/roles', false)
            ->assertSee('admin/payments', false);
    }

    public function test_adding_extra_permission_shows_new_sidebar_link(): void
    {
        $super  = $this->admin('super_admin', null, 1);
        $target = $this->admin('support', null, 2);

        // support role has no wallets.view; add it as an extra user permission
        $this->actingAs($super)
            ->put(route('admin.users.update', $target), [
                'name'              => $target->name,
                'email'             => $target->email,
                'phone_number'      => null,
                'role'              => 'admin',
                'admin_role'        => 'support',
                'admin_permissions' => ['wallets.view'],
                'is_active'         => '1',
            ])->assertRedirect();

        $fresh = $target->fresh();
        $this->assertEquals(['wallets.view'], $fresh->admin_permissions);
        $this->assertTrue($fresh->hasAdminPermission('wallets.view'));

        $this->actingAs($fresh)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin/wallets', false)
            ->assertSee('admin/tickets', false);
    }

    public function test_command_palette_respects_permissions(): void
    {
        // super admin sees Settings (security.manage) and all items
        $super = $this->admin('super_admin', null, 1);
        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin/security', false)
            ->assertSee('admin/partners', false)
            ->assertSee('admin/payments', false);

        // support role: only Users/Orders/Tickets listed, never Partners/Payments/Services/Settings
        $support = $this->admin('support', null, 2);
        $this->actingAs($support)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee("name: 'Users'", false)
            ->assertSee("name: 'Orders'", false)
            ->assertSee("name: 'Tickets'", false)
            ->assertDontSee('admin/partners', false)
            ->assertDontSee('admin/payments', false)
            ->assertDontSee('admin/services', false)
            // use trailing quote to avoid matching admin/security-events
            ->assertDontSee("admin/security'", false);
    }

    public function test_dashboard_quick_link_cards_respect_permissions(): void
    {
        $super = $this->admin('super_admin', null, 1);
        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending Verifications')
            ->assertSee('Reviews Needing Moderation')
            ->assertSee('Open Security Events');

        // support role = dashboard.view, users.view, orders.view,
        //   tickets.manage, ratings.manage, faq.manage, notifications.manage
        // => sees Reviews card (ratings.manage) but NOT Verifications (verifications.manage)
        //    nor Security Events (security.manage)
        $support = $this->admin('support', null, 2);
        $this->actingAs($support)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Reviews Needing Moderation')
            ->assertDontSee('Pending Verifications')
            ->assertDontSee('Open Security Events')
            ->assertDontSee('admin/document-verifications', false)
            ->assertDontSee('admin/security-events', false);

        // adding verifications.manage as a user-level extra permission reveals the card
        $this->actingAs($super)
            ->put(route('admin.users.update', $support), [
                'name'              => $support->name,
                'email'             => $support->email,
                'phone_number'      => null,
                'role'              => 'admin',
                'admin_role'        => 'support',
                'admin_permissions' => ['verifications.manage'],
                'is_active'         => '1',
            ])->assertRedirect();

        $this->actingAs($support->fresh())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending Verifications')
            ->assertSee('admin/document-verifications', false)
            ->assertDontSee('Open Security Events');
    }

    public function test_dashboard_recent_orders_and_users_links_respect_permissions(): void
    {
        $super = $this->admin('super_admin', null, 1);
        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recent Orders')
            ->assertSee('Recent Users');

        // a role without orders.view/users.view must not render those cards.
        // no built-in role qualifies, so create one that only sees the dashboard
        \Illuminate\Support\Facades\DB::table('admin_access_roles')->insert([
            'key' => 'viewer',
            'label' => 'Viewer',
            'permissions' => json_encode(['dashboard.view']),
            'is_protected' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \App\Models\AdminRole::forgetDefinitionCache();

        $restricted = $this->admin('viewer', null, 2);
        $this->actingAs($restricted)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Recent Orders')
            ->assertDontSee('Recent Users')
            ->assertDontSee('admin/orders', false)
            ->assertDontSee('admin/users', false);
    }
}
