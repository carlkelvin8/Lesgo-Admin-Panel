<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_fee_payments')) {
            return;
        }

        // This is intentionally a new migration. Some production databases ran
        // the original table migration before waiver support was added to it.
        if (!Schema::hasColumn('registration_fee_payments', 'waived_at')) {
            Schema::table('registration_fee_payments', function (Blueprint $table) {
                $table->timestamp('waived_at')->nullable()->after('payment_date');
            });
        }

        if (!Schema::hasColumn('registration_fee_payments', 'waived_by')) {
            Schema::table('registration_fee_payments', function (Blueprint $table) {
                $table->foreignId('waived_by')->nullable()->after('waived_at')->constrained('users')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('registration_fee_payments', 'waiver_reason')) {
            Schema::table('registration_fee_payments', function (Blueprint $table) {
                $table->text('waiver_reason')->nullable()->after('waived_by');
            });
        }

        // MySQL ENUM definitions from installations created before waiver
        // support do not accept the `waived` value and fail the admin action.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE registration_fee_payments MODIFY payment_status ENUM('unpaid','pending','paid','waived','failed','expired') NOT NULL DEFAULT 'unpaid'");
        }
    }

    public function down(): void
    {
        // Schema repair is deliberately irreversible: removing these fields can
        // destroy the audit trail for waivers already granted by administrators.
    }
};
