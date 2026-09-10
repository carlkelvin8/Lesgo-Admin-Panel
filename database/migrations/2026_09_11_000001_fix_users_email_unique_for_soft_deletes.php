<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            try {
                DB::statement('DROP INDEX IF EXISTS users_email_unique');
            } catch (\Throwable $e) {}
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique_active ON users (email) WHERE deleted_at IS NULL');
        } else {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['email']);
                });
            } catch (\Throwable $e) {
                try {
                    DB::statement('DROP INDEX users_email_unique ON users');
                } catch (\Throwable $inner) {}
            }
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('email');
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_email_unique_active');
            try {
                DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
            } catch (\Throwable $e) {}
        } else {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex(['email']);
                });
            } catch (\Throwable $e) {}
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('email');
                });
            } catch (\Throwable $e) {}
        }
    }
};
