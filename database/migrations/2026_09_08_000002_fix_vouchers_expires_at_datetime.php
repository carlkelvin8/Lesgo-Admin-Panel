<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vouchers')) {
            try {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement('ALTER TABLE vouchers ALTER COLUMN expires_at TYPE TIMESTAMP USING expires_at::timestamp');
                } else {
                    Schema::table('vouchers', function (Blueprint $table) {
                        $table->timestamp('expires_at')->nullable()->change();
                    });
                }
            } catch (\Throwable $e) {
                try {
                    Schema::table('vouchers', function (Blueprint $table) {
                        $table->dateTime('expires_at')->nullable()->change();
                    });
                } catch (\Throwable $e2) {}
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vouchers')) {
            try {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    DB::statement('ALTER TABLE vouchers ALTER COLUMN expires_at TYPE DATE USING expires_at::date');
                } else {
                    Schema::table('vouchers', function (Blueprint $table) {
                        $table->date('expires_at')->nullable()->change();
                    });
                }
            } catch (\Throwable $e) {}
        }
    }
};
