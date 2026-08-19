<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'admin_role')) {
                    $table->string('admin_role', 40)->nullable()->index();
                }

                if (! Schema::hasColumn('users', 'admin_permissions')) {
                    $table->json('admin_permissions')->nullable();
                }

                if (! Schema::hasColumn('users', 'password_changed_at')) {
                    $table->timestamp('password_changed_at')->nullable();
                }
            });

            DB::table('users')
                ->where('role', 'admin')
                ->whereNull('admin_role')
                ->update(['admin_role' => 'super_admin']);
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (! Schema::hasColumn('notifications', 'delivery_status')) {
                    $table->string('delivery_status', 30)->default('pending')->index();
                }

                if (! Schema::hasColumn('notifications', 'delivery_attempts')) {
                    $table->unsignedSmallInteger('delivery_attempts')->default(0);
                }

                if (! Schema::hasColumn('notifications', 'delivered_via')) {
                    $table->string('delivered_via', 50)->nullable();
                }

                if (! Schema::hasColumn('notifications', 'delivery_reference')) {
                    $table->string('delivery_reference')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'sent_at')) {
                    $table->timestamp('sent_at')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable();
                }

                if (! Schema::hasColumn('notifications', 'failure_reason')) {
                    $table->text('failure_reason')->nullable();
                }
            });

            DB::table('notifications')
                ->where('channel', 'in_app')
                ->where('delivery_status', 'pending')
                ->update([
                    'delivery_status' => 'delivered',
                    'delivered_via' => 'database',
                    'sent_at' => DB::raw('created_at'),
                ]);
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'refund_status')) {
                    $table->string('refund_status', 30)->default('none')->index();
                }

                if (! Schema::hasColumn('payments', 'refunded_amount')) {
                    $table->decimal('refunded_amount', 10, 2)->default(0);
                }

                if (! Schema::hasColumn('payments', 'refund_reason')) {
                    $table->text('refund_reason')->nullable();
                }

                if (! Schema::hasColumn('payments', 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable();
                }

                if (! Schema::hasColumn('payments', 'reconciled_at')) {
                    $table->timestamp('reconciled_at')->nullable();
                }

                if (! Schema::hasColumn('payments', 'reconciliation_status')) {
                    $table->string('reconciliation_status', 30)->default('unreconciled')->index();
                }

                if (! Schema::hasColumn('payments', 'reconciled_by')) {
                    $table->unsignedBigInteger('reconciled_by')->nullable();
                }

                if (! Schema::hasColumn('payments', 'reconciliation_notes')) {
                    $table->text('reconciliation_notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('wallet_top_ups')) {
            Schema::table('wallet_top_ups', function (Blueprint $table) {
                if (! Schema::hasColumn('wallet_top_ups', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }

                if (! Schema::hasColumn('wallet_top_ups', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                }

                if (! Schema::hasColumn('wallet_top_ups', 'review_notes')) {
                    $table->text('review_notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('wallet_transactions')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('wallet_transactions', 'reference')) {
                    $table->string('reference')->nullable();
                }

                if (! Schema::hasColumn('wallet_transactions', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Operational history is intentionally retained on rollback.
    }
};
