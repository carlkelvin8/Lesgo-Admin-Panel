<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Intentionally left blank. The generic admin_roles name collides with
        // an existing production table owned by another part of the platform.
        // The feature uses admin_access_roles in the follow-up migration.
    }

    public function down(): void
    {
        // The pre-existing admin_roles table must never be modified or dropped.
    }
};
