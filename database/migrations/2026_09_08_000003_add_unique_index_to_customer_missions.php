<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_missions', function (Blueprint $table) {
            try {
                $table->unique(['user_id', 'mission_type', 'mission_date'], 'customer_missions_unique');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('customer_missions', function (Blueprint $table) {
            try {
                $table->dropUnique('customer_missions_unique');
            } catch (\Throwable $e) {}
        });
    }
};
