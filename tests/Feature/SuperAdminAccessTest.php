<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_super_admin_keeps_full_access_when_database_permissions_are_stale(): void
    {
        AdminRole::query()->whereKey('super_admin')->update([
            'permissions' => [],
        ]);
        AdminRole::forgetDefinitionCache();

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertTrue($admin->hasAdminPermission('dashboard.view'));
        $this->assertTrue($admin->hasAdminPermission('partners.view'));
        $this->assertTrue($admin->hasAdminPermission('partners.manage'));
        $this->assertTrue($admin->hasAdminPermission('roles.manage'));
    }

    public function test_inactive_super_admin_remains_blocked(): void
    {
        $admin = User::factory()->make([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => false,
        ]);

        $this->assertFalse($admin->hasAdminPermission('partners.view'));
    }
}
