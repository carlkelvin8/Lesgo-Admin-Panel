<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_fee_payments')) {
            return;
        }

        DB::transaction(function () {
            DB::table('registration_fee_payments')
                ->where('payment_status', 'waived')
                ->where('application_status', 'pending')
                ->orderBy('id')
                ->each(function ($fee) {
                    $approvedAt = $fee->waived_at ?? now();

                    DB::table('registration_fee_payments')
                        ->where('id', $fee->id)
                        ->update([
                            'application_status' => 'approved',
                            'approved_at' => $approvedAt,
                            'approved_by' => $fee->waived_by,
                            'is_active' => true,
                            'activated_at' => $fee->activated_at ?? $approvedAt,
                            'updated_at' => now(),
                        ]);

                    if ($fee->account_type === 'rider' && Schema::hasTable('driver_profiles')) {
                        DB::table('driver_profiles')
                            ->where('user_id', $fee->user_id)
                            ->update(['status' => 'active', 'updated_at' => now()]);
                    }

                    if ($fee->account_type === 'merchant' && Schema::hasTable('partners')) {
                        DB::table('partners')
                            ->where('user_id', $fee->user_id)
                            ->update(['status' => 'active', 'updated_at' => now()]);
                    }
                });
        });
    }

    public function down(): void
    {
        // This migration resolves explicit, audited admin waivers. Reverting an
        // approved account automatically would incorrectly undo admin intent.
    }
};
