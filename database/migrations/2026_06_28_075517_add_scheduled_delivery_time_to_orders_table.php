<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add scheduled delivery time column
            $table->timestamp('scheduled_delivery_time')
                ->nullable()
                ->after('estimated_delivery_time')
                ->comment('When customer wants delivery (NULL = ASAP)');
            
            // Add index for efficient queries on scheduled orders
            $table->index(['scheduled_delivery_time', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['scheduled_delivery_time', 'status']);
            $table->dropColumn('scheduled_delivery_time');
        });
    }
};
