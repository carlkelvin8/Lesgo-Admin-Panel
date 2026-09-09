<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_upload_and_remove_their_profile_picture(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone_number' => $admin->phone_number,
            'profile_picture' => UploadedFile::fake()->image('administrator.jpg'),
        ])->assertSessionHas('success');

        $path = $admin->fresh()->profile_picture;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => $admin->name,
            'email' => $admin->email,
            'phone_number' => $admin->phone_number,
            'remove_profile_picture' => '1',
        ])->assertSessionHas('success');

        $this->assertNull($admin->fresh()->profile_picture);
        Storage::disk('public')->assertMissing($path);
    }
}
