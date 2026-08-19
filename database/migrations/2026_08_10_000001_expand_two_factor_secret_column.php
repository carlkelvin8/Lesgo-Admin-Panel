<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('two_factor_auth', function (Blueprint $table) {
            // Laravel's encrypted payload is longer than VARCHAR(255).
            $table->text('secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep the safe width when rolling back. Narrowing this column can
        // truncate encrypted secrets and lock users out of their accounts.
    }
};
