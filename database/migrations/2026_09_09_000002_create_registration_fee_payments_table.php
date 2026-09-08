<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('registration_fee_payments')) {
            return;
        }

        Schema::create('registration_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('account_type', 20);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('PHP');
            $table->string('paymongo_checkout_id')->nullable()->index();
            $table->string('paymongo_reference')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->string('checkout_url', 500)->nullable();
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'expired'])->default('unpaid')->index();
            $table->timestamp('payment_date')->nullable();
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_grandfathered')->default(false);
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'account_type'], 'uniq_reg_fee_user_type');
            $table->index(['account_type', 'payment_status']);
            $table->index(['account_type', 'application_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_fee_payments');
    }
};
