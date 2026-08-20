<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_soft_delete_another_user_from_the_admin_ui(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertSee('Delete')
            ->assertSee(route('admin.users.destroy', $customer));

        $this->delete(route('admin.users.destroy', $customer))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertSoftDeleted('users', ['id' => $customer->id]);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_view_only_admin_does_not_see_user_management_actions(): void
    {
        $finance = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'finance',
            'is_active' => true,
        ]);
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->actingAs($finance)
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertDontSee(route('admin.users.destroy', $customer))
            ->assertDontSee(route('admin.users.edit', $customer));

        $this->delete(route('admin.users.destroy', $customer))->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $customer->id]);
    }
}
