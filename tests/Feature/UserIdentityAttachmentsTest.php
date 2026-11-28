<?php

namespace Tests\Feature;

use App\Models\DocumentVerification;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIdentityAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]));
    }

    public function test_user_details_show_registration_selfie_and_both_uploaded_id_images(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'profile_picture' => null,
            'profile_photo_url' => null,
        ]);

        DocumentVerification::create([
            'user_id' => $customer->id,
            'document_type' => 'valid_id',
            'document_urls' => [
                'https://media.example.test/customer/id-front.jpg',
                'https://media.example.test/customer/id-back.jpg',
            ],
            'metadata' => ['selfie_url' => 'https://media.example.test/customer/selfie.jpg'],
            'status' => 'pending',
            'submitted_at' => '2026-01-15 10:00:00',
        ]);

        $this->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertSee('src="https://media.example.test/customer/selfie.jpg"', false)
            ->assertSee('src="https://media.example.test/customer/id-front.jpg"', false)
            ->assertSee('src="https://media.example.test/customer/id-back.jpg"', false)
            ->assertSee('href="https://media.example.test/customer/id-front.jpg" target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('Open ID Front')
            ->assertSee('Open ID Back')
            ->assertSee('Pending')
            ->assertSee('Jan 15, 2026');
    }

    public function test_admin_can_find_customers_who_need_phone_verification(): void
    {
        $missing = User::factory()->create(['phone_number' => null]);
        $unverified = User::factory()->create(['phone_number' => '+639171234567']);
        $verified = User::factory()->create(['phone_number' => '+639181234567']);
        $verified->forceFill(['phone_verified_at' => now()])->save();

        foreach (['missing' => $missing, 'unverified' => $unverified, 'verified' => $verified] as $status => $user) {
            $this->get(route('admin.users.index', ['role' => 'customer', 'phone_status' => $status]))
                ->assertOk()
                ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$user->id]);
        }
        $this->get(route('admin.users.show', $missing))->assertSee('No number linked');
        $this->get(route('admin.users.show', $verified))->assertSee('OTP verified');

        $this->put(route('admin.users.update', $verified), [
            'name' => 'Updated Customer',
            'email' => $verified->email,
            'phone_number' => '+639191234567',
            'role' => 'customer',
            'is_active' => 1,
        ])->assertSessionHas('success');
        $this->assertNull($verified->fresh()->phone_verified_at);
    }

    public function test_admin_without_verification_permission_cannot_view_id_attachments(): void
    {
        $customer = User::factory()->create();
        DocumentVerification::create([
            'user_id' => $customer->id,
            'document_type' => 'valid_id',
            'document_urls' => ['https://media.example.test/private-id.jpg'],
        ]);

        $support = User::factory()->create([
            'role' => 'admin', 'admin_role' => 'support', 'is_active' => true,
            'admin_permissions' => ['users.view'],
        ]);

        $this->actingAs($support)->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertDontSee('Identity Documents')
            ->assertDontSee('private-id.jpg');
    }

    public function test_partner_edit_hides_business_type_and_preserves_legacy_value(): void
    {
        $partner = Partner::factory()->create([
            'business_type' => 'restaurant',
            'category' => 'Food',
        ]);

        $this->get(route('admin.partners.edit', $partner))
            ->assertOk()
            ->assertDontSee('Business Type')
            ->assertDontSee('name="business_type"', false)
            ->assertSee('name="category"', false);

        $this->put(route('admin.partners.update', $partner), [
            'name' => $partner->name,
            'legal_name' => $partner->legal_name,
            'category' => $partner->category,
            'status' => $partner->status,
        ])->assertRedirect(route('admin.partners.show', $partner));

        $this->assertSame('restaurant', $partner->fresh()->business_type);
    }
}
