<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            try {
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique');
            } catch (\Throwable $e) {}
            try {
                DB::statement('DROP INDEX IF EXISTS users_email_unique');
            } catch (\Throwable $e) {}
            try {
                DB::statement('DROP INDEX IF EXISTS users_email_unique_active');
            } catch (\Throwable $e) {}

            $hasDuplicates = false;
            try {
                $dup = DB::select("SELECT email, COUNT(*) c FROM users WHERE deleted_at IS NULL GROUP BY email HAVING COUNT(*) > 1 LIMIT 1");
                $hasDuplicates = !empty($dup);
            } catch (\Throwable $e) {}

            if ($hasDuplicates) {
                \Illuminate\Support\Facades\Log::warning('Skipping partial unique index: duplicate active emails exist');
                DB::statement('CREATE INDEX IF NOT EXISTS users_email_unique_active ON users (email) WHERE deleted_at IS NULL');
                return;
            }

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
