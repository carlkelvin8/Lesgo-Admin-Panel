<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')
            ->where('code', 'LESRIDE')
            ->update([
                'description' => 'Tricycle ride service',
                'base_fare' => 60.00,
                'per_km_rate' => 15.00,
                'minimum_fare' => 60.00,
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('services')
            ->where('code', 'LESRIDE')
            ->update([
                'description' => 'Motorcycle ride service',
                'base_fare' => 30.00,
                'per_km_rate' => 8.00,
                'minimum_fare' => 30.00,
                'updated_at' => now(),
            ]);
    }
};
