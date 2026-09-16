<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_image_url_prefers_profile_picture(): void
    {
        $user = User::factory()->create([
            'profile_picture' => 'profile-pictures/custom.jpg',
            'profile_photo_url' => '/api/v1/storage/legacy.jpg',
        ]);

        $this->assertEquals(env('APP_URL', 'http://localhost').'/storage/profile-pictures/custom.jpg', $user->profileImageUrl());
    }

    public function test_profile_image_url_falls_back_to_profile_photo_url(): void
    {
        $user = User::factory()->create([
            'profile_picture' => null,
            'profile_photo_url' => 'https://storage.example.com/rider-uploads/rider.jpg',
        ]);

        $this->assertEquals('https://storage.example.com/rider-uploads/rider.jpg', $user->profileImageUrl());
    }

    public function test_profile_image_url_falls_back_to_local_profile_photo_path(): void
    {
        $user = User::factory()->create([
            'profile_picture' => null,
            'profile_photo_url' => 'profile-pictures/uploaded.jpg',
        ]);

        $this->assertEquals(env('APP_URL', 'http://localhost').'/storage/profile-pictures/uploaded.jpg', $user->profileImageUrl());
    }

    public function test_profile_image_url_returns_null_when_no_image(): void
    {
        $user = User::factory()->create([
            'profile_picture' => null,
            'profile_photo_url' => null,
        ]);

        $this->assertNull($user->profileImageUrl());
    }
}