<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('audit_logs', 'description')) {
                    $table->string('description')->nullable();
                }
            });

            if (Schema::hasColumn('audit_logs', 'event_category')) {
                DB::table('audit_logs')
                    ->where('event_category', 'administration')
                    ->update(['event_category' => 'data_modification']);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'description')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};