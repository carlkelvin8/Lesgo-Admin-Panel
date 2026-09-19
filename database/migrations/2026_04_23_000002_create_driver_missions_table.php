<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mission templates (defined by admin)
        if (!Schema::hasTable('mission_templates')) {
        Schema::create('mission_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // daily, weekly, monthly, one_time
            $table->string('goal_type'); // complete_orders, get_rating, refer_friend, specific_service
            $table->integer('goal_target'); // e.g., 10 orders, 5 stars
            $table->decimal('reward_amount', 10, 2);
            $table->string('reward_currency')->default('PHP');
            $table->string('service_code')->nullable(); // for specific_service type (lesride, leseat, etc.)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        }

        // Driver mission progress (per driver)
        if (!Schema::hasTable('driver_missions')) {
            Schema::create('driver_missions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
                $table->foreignId('mission_template_id')->constrained('mission_templates')->cascadeOnDelete();
                $table->integer('current_progress')->default(0);
                $table->integer('goal_target');
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->boolean('reward_claimed')->default(false);
                $table->timestamp('claimed_at')->nullable();
                $table->date('mission_date'); // for daily missions
                $table->timestamps();

                // Unique constraint: one mission per driver per day (short name for MySQL 64-char limit)
                $table->unique(['driver_profile_id', 'mission_template_id', 'mission_date'], 'drv_mission_daily');
            });
        }

        // Repair path: an earlier failed run may have left the table without its
        // unique index (or with the old long-named one). Driver-agnostic via
        // Schema::getIndexes(), and tolerant of races/stale state so migrate
        // never fatals with "Duplicate key name".
        if (Schema::hasTable('driver_missions')) {
            try {
                $indexes = collect(Schema::getIndexes('driver_missions'))->pluck('name');
            } catch (\Throwable) {
                $indexes = collect();
            }
            if ($indexes->contains('driver_missions_driver_profile_id_mission_template_id_mission_date_unique')) {
                try {
                    Schema::table('driver_missions', function (Blueprint $table) {
                        $table->dropUnique('driver_missions_driver_profile_id_mission_template_id_mission_date_unique');
                    });
                } catch (\Throwable) {
                }
            }
            if (! $indexes->contains('drv_mission_daily')) {
                try {
                    Schema::table('driver_missions', function (Blueprint $table) {
                        $table->unique(['driver_profile_id', 'mission_template_id', 'mission_date'], 'drv_mission_daily');
                    });
                } catch (\Throwable) {
                    // Index already exists (stale/parallel state) — safe to ignore.
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_missions');
        Schema::dropIfExists('mission_templates');
    }
};
