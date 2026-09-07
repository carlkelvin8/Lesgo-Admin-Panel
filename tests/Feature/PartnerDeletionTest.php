<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_delete_partners_and_their_owner_accounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $partners = Partner::factory()->count(2)->create();
        $ownerIds = $partners->pluck('user_id');

        $this->actingAs($admin)
            ->delete(route('admin.partners.bulk-destroy'), ['ids' => $partners->pluck('id')->all()])
            ->assertSessionHas('success');

        foreach ($partners as $partner) {
            $this->assertDatabaseMissing('partners', ['id' => $partner->id]);
        }
        foreach ($ownerIds as $ownerId) {
            $this->assertDatabaseMissing('users', ['id' => $ownerId]);
        }
    }
}
