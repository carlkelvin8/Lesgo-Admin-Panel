<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_roles')) {
            Schema::create('admin_roles', function (Blueprint $table) {
                $table->string('key', 40)->primary();
                $table->string('label', 100);
                $table->json('permissions');
                $table->boolean('is_protected')->default(false);
                $table->timestamps();
            });
        }

        $now = now();
        $roles = collect(config('admin.roles', []))
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                'label' => $definition['label'],
                'permissions' => json_encode(array_values($definition['permissions'])),
                'is_protected' => $key === 'super_admin',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($roles !== []) {
            DB::table('admin_roles')->insertOrIgnore($roles);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
