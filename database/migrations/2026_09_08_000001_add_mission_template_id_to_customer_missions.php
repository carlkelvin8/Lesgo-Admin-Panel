<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_missions', function (Blueprint $table) {
            $table->foreignId('mission_template_id')->nullable()->after('user_id')->constrained('mission_templates')->nullOnDelete();
            $table->index(['mission_template_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_missions', function (Blueprint $table) {
            $table->dropForeign(['mission_template_id']);
            $table->dropColumn('mission_template_id');
        });
    }
};
