<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('legacySuperAdminRoleProvider')]
    public function test_legacy_super_admin_role_values_keep_full_sidebar_access(?string $adminRole): void
    {
        $admin = User::factory()->make([
            'role' => 'admin',
            'admin_role' => $adminRole,
            'is_active' => true,
        ]);

        $this->assertSame('super_admin', $admin->effectiveAdminRole());
        $this->assertTrue($admin->hasAdminPermission('roles.manage'));
        $this->assertTrue($admin->hasAdminPermission('missions.manage'));
        $this->assertTrue($admin->hasAdminPermission('security.manage'));
    }

    public static function legacySuperAdminRoleProvider(): array
    {
        return [
            'null' => [null],
            'title case with space' => ['Super Admin'],
            'hyphenated' => ['super-admin'],
            'uppercase padded' => ['  SUPER_ADMIN  '],
        ];
    }
}
