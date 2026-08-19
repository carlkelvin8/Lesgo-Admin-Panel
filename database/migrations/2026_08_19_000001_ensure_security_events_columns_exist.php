<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event_type');
                $table->string('severity')->default('warning');
                $table->string('source')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('description')->nullable();
                $table->json('event_data')->nullable();
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->string('resolved_by')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('detected_at')->useCurrent();
                $table->timestamps();

                $table->index(['severity', 'is_resolved']);
                $table->index(['event_type', 'detected_at']);
                $table->index(['user_id', 'detected_at']);
                $table->index(['ip_address', 'detected_at']);
            });

            return;
        }

        $missingColumns = array_values(array_filter([
            'user_id',
            'event_type',
            'severity',
            'source',
            'ip_address',
            'user_agent',
            'description',
            'event_data',
            'is_resolved',
            'resolved_at',
            'resolved_by',
            'resolution_notes',
            'metadata',
            'detected_at',
            'created_at',
            'updated_at',
        ], fn (string $column): bool => ! Schema::hasColumn('security_events', $column)));

        if ($missingColumns === []) {
            return;
        }

        Schema::table('security_events', function (Blueprint $table) use ($missingColumns) {
            if (in_array('user_id', $missingColumns, true)) {
                // Keep the repair additive and compatible with legacy production data.
                $table->unsignedBigInteger('user_id')->nullable();
            }

            if (in_array('event_type', $missingColumns, true)) {
                $table->string('event_type')->nullable();
            }

            if (in_array('severity', $missingColumns, true)) {
                $table->string('severity')->default('warning');
            }

            if (in_array('source', $missingColumns, true)) {
                $table->string('source')->nullable();
            }

            if (in_array('ip_address', $missingColumns, true)) {
                $table->string('ip_address', 45)->nullable();
            }

            if (in_array('user_agent', $missingColumns, true)) {
                $table->text('user_agent')->nullable();
            }

            if (in_array('description', $missingColumns, true)) {
                $table->text('description')->nullable();
            }

            if (in_array('event_data', $missingColumns, true)) {
                $table->json('event_data')->nullable();
            }

            if (in_array('is_resolved', $missingColumns, true)) {
                $table->boolean('is_resolved')->default(false);
            }

            if (in_array('resolved_at', $missingColumns, true)) {
                $table->timestamp('resolved_at')->nullable();
            }

            if (in_array('resolved_by', $missingColumns, true)) {
                $table->string('resolved_by')->nullable();
            }

            if (in_array('resolution_notes', $missingColumns, true)) {
                $table->text('resolution_notes')->nullable();
            }

            if (in_array('metadata', $missingColumns, true)) {
                $table->json('metadata')->nullable();
            }

            if (in_array('detected_at', $missingColumns, true)) {
                $table->timestamp('detected_at')->nullable();
            }

            if (in_array('created_at', $missingColumns, true)) {
                $table->timestamp('created_at')->nullable();
            }

            if (in_array('updated_at', $missingColumns, true)) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // This migration repairs legacy schemas and intentionally keeps data intact.
    }
};
