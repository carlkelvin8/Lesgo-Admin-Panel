<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mission_reward_payouts')) {
            return;
        }

        Schema::create('mission_reward_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
            $table->foreignId('mission_id')->constrained('driver_missions')->cascadeOnDelete();
            $table->foreignId('mission_template_id')->constrained('mission_templates')->cascadeOnDelete();
            $table->decimal('reward_amount', 10, 2);
            $table->string('reward_currency', 10)->default('PHP');
            $table->string('paymongo_transfer_id')->nullable()->unique();
            $table->string('paymongo_reference')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->string('provider', 20)->default('paymongo');
            $table->enum('status', ['pending', 'processing', 'successful', 'failed'])->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->integer('attempts')->default(0);
            $table->json('paymongo_payload')->nullable();
            $table->json('paymongo_response')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique('mission_id', 'uniq_mission_reward_mission');
            $table->index(['rider_user_id', 'status']);
            $table->index('mission_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_reward_payouts');
    }
};
