<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_fee_payments')) return;

        Schema::table('registration_fee_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('registration_fee_payments', 'waived_at')) $table->timestamp('waived_at')->nullable()->after('payment_date');
            if (!Schema::hasColumn('registration_fee_payments', 'waived_by')) $table->foreignId('waived_by')->nullable()->after('waived_at')->constrained('users')->nullOnDelete();
            if (!Schema::hasColumn('registration_fee_payments', 'waiver_reason')) $table->text('waiver_reason')->nullable()->after('waived_by');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE registration_fee_payments MODIFY payment_status ENUM('unpaid','pending','paid','waived','failed','expired') NOT NULL DEFAULT 'unpaid'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('registration_fee_payments')) return;
        Schema::table('registration_fee_payments', function (Blueprint $table) {
            if (Schema::hasColumn('registration_fee_payments', 'waived_by')) $table->dropConstrainedForeignId('waived_by');
            $columns = array_values(array_filter(['waived_at', 'waiver_reason'], fn ($column) => Schema::hasColumn('registration_fee_payments', $column)));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
