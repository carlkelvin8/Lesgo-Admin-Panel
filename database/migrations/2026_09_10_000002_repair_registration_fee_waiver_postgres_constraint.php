<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::getDriverName() !== 'pgsql'
            || !Schema::hasTable('registration_fee_payments')
            || !Schema::hasColumn('registration_fee_payments', 'payment_status')
        ) {
            return;
        }

        DB::statement(<<<'SQL'
            DO $$
            DECLARE
                constraint_record record;
            BEGIN
                FOR constraint_record IN
                    SELECT con.conname
                    FROM pg_constraint con
                    JOIN pg_class rel ON rel.oid = con.conrelid
                    JOIN pg_namespace ns ON ns.oid = rel.relnamespace
                    WHERE con.contype = 'c'
                      AND rel.relname = 'registration_fee_payments'
                      AND ns.nspname = current_schema()
                      AND pg_get_constraintdef(con.oid) ILIKE '%payment_status%'
                LOOP
                    EXECUTE format(
                        'ALTER TABLE registration_fee_payments DROP CONSTRAINT %I',
                        constraint_record.conname
                    );
                END LOOP;
            END $$
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE registration_fee_payments
            ADD CONSTRAINT registration_fee_payments_payment_status_check
            CHECK (payment_status IN ('unpaid', 'pending', 'paid', 'waived', 'failed', 'expired'))
        SQL);
    }

    public function down(): void
    {
        // Keep existing waiver records valid.
    }
};
