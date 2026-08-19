<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_top_ups', function (Blueprint $table) {
            $table->string('provider', 20)->default('xendit')->after('payment_method');
            $table->string('gateway_reference')->nullable()->index()->after('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_top_ups', function (Blueprint $table) {
            $table->dropColumn(['provider', 'gateway_reference']);
        });
    }
};
