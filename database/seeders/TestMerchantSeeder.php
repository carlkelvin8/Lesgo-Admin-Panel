<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Partner;
use App\Models\PartnerBranch;
use App\Models\Wallet;

class TestMerchantSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'merchant@test.com'],
            [
                'name' => 'Test Merchant',
                'phone_number' => '+639123456789',
                'password' => Hash::make('Password123!'),
                'role' => 'partner_admin',
                'referral_code' => 'MERCHANT1',
                'points' => 0,
            ]
        );

        $partner = Partner::firstOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'LesGo Test Store',
                'slug' => 'lesgo-test-store',
                'status' => 'active',
                'category' => 'restaurant',
                'description' => 'Test merchant store for development',
                'delivery_fee' => 50,
            ]
        );

        // Create primary branch with Santa Praxedes, Cagayan coordinates
        PartnerBranch::firstOrCreate(
            ['partner_id' => $partner->id, 'is_primary' => true],
            [
                'name'         => 'LesGo Test Store (Main)',
                'address_line1' => 'Santa Praxedes, Cagayan',
                'city'         => 'Santa Praxedes',
                'region'       => 'Cagayan Valley',
                'country'      => 'Philippines',
                'postal_code'  => '3517',
                'latitude'     => 18.5187555,
                'longitude'    => 120.9991032,
                'is_primary'   => true,
            ]
        );

        $this->command->info("Merchant created: {$user->email} / Password123!");
        $this->command->info("Partner ID: {$partner->id}");

        // Create test rider
        $rider = User::firstOrCreate(
            ['email' => 'testrider@lesgo.com'],
            [
                'name' => 'Test Rider',
                'phone_number' => '+639111222333',
                'password' => Hash::make('TestRider123!'),
                'role' => 'driver',
                'referral_code' => 'RIDER001',
                'points' => 0,
            ]
        );

        // Create driver profile
        \App\Models\DriverProfile::firstOrCreate(
            ['user_id' => $rider->id],
            [
                'status' => 'active',
                'license_number' => 'N01-23-456789',
                'vehicle_type' => 'motorcycle',
                'plate_number' => 'ABC-1234',
            ]
        );

        // Seed LesPay wallet for the test rider with ₱10,000
        Wallet::updateOrCreate(
            ['user_id' => $rider->id],
            ['balance' => 10000.00, 'currency' => 'PHP']
        );

        $this->command->info("Rider created: {$rider->email} / TestRider123!");
        $this->command->info("Rider LesPay wallet: PHP 10,000.00");
    }
}
