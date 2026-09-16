<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_partner_with_empty_delivery_fee_does_not_error(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $owner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($admin)
            ->post(route('admin.partners.store'), [
                'user_id' => $owner->id,
                'name' => 'Sample Partner',
                'delivery_fee' => '',
            ])
            ->assertRedirect(route('admin.partners.index'));

        $this->assertDatabaseHas('partners', ['name' => 'Sample Partner', 'delivery_fee' => 0]);

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'admin_action',
            'description' => "Created partner 'Sample Partner'",
            'event_category' => 'data_modification',
            'risk_level' => 'low',
            'user_id' => $admin->id,
        ]);
    }

    public function test_update_partner_empty_delivery_fee_preserves_zero(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $partner = Partner::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.partners.update', $partner), [
                'name' => $partner->name,
                'status' => 'pending',
                'delivery_fee' => '',
            ])
            ->assertRedirect(route('admin.partners.show', $partner));

        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'delivery_fee' => 0]);

        $log = \App\Models\AuditLog::where('event_type', 'admin_action')
            ->where('resource_type', 'Partner')
            ->where('resource_id', $partner->id)
            ->latest('occurred_at')
            ->first();

        $this->assertNotNull($log, 'Expected an audit log for the partner update.');
        $this->assertStringStartsWith('Updated partner', $log->description);
        $this->assertNotEmpty($log->old_values, 'Update audit log should capture old values.');
        $this->assertContains($log->event_category, ['data_modification', 'security', 'authorization', 'user_activity', 'authentication']);
    }
}