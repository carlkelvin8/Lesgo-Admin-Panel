<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function testIsAdminReturnsTrueForAdminRole(): void
    {
        $user = User::factory()->make(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
    }

    public function testIsAdminReturnsFalseForNonAdmin(): void
    {
        $user = User::factory()->make(['role' => 'customer']);

        $this->assertFalse($user->isAdmin());
    }

    public function testIsSuperAdminReturnsTrueForSuperAdmin(): void
    {
        $user = User::factory()->make(['role' => 'admin', 'admin_role' => 'super_admin']);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function testEffectiveAdminRoleReturnsSuperAdminWhenNull(): void
    {
        $user = User::factory()->make(['admin_role' => null, 'role' => 'admin']);

        $this->assertEquals('super_admin', $user->effectiveAdminRole());
    }

    public function testAdminRoleLabelReturnsAdministratorForUnknownRole(): void
    {
        $user = User::factory()->make(['role' => 'admin', 'admin_role' => 'operations']);

        $label = $user->adminRoleLabel();

        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }

    public function testHasAdminPermissionReturnsTrueForWildcard(): void
    {
        $user = User::factory()->make([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->assertTrue($user->hasAdminPermission('any.permission'));
    }

    public function testHasAdminPermissionReturnsFalseForInactiveUser(): void
    {
        $user = User::factory()->make(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => false]);

        $this->assertFalse($user->hasAdminPermission('dashboard.view'));
    }
}
