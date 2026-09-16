<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerShowDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_partner_with_documents_does_not_error(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $partner = Partner::factory()->create([
            'documents' => [
                'selfie' => 'partners/documents/selfie.jpg',
                'valid_id' => 'partners/documents/valid_id.jpg',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.partners.show', $partner))
            ->assertOk()
            ->assertSee($partner->name);
    }

    public function test_show_partner_without_documents_still_works(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $partner = Partner::factory()->create(['documents' => null]);

        $this->actingAs($admin)
            ->get(route('admin.partners.show', $partner))
            ->assertOk()
            ->assertSee($partner->name);
    }
}