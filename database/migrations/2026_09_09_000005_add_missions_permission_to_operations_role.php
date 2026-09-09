<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_access_roles')) {
            return;
        }

        foreach (DB::table('admin_access_roles')->get() as $role) {
            $permissions = json_decode($role->permissions, true) ?: [];

            if (in_array('*', $permissions, true)) {
                continue;
            }

            $required = $role->key === 'operations'
                ? ['dashboard.view', 'missions.manage']
                : ['dashboard.view'];
            $updated = array_values(array_unique([...$permissions, ...$required]));

            if ($updated !== $permissions) {
                DB::table('admin_access_roles')->where('key', $role->key)->update([
                    'permissions' => json_encode($updated),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_access_roles')) {
            return;
        }

        $role = DB::table('admin_access_roles')->where('key', 'operations')->first();

        if (! $role) {
            return;
        }

        $permissions = array_values(array_filter(
            json_decode($role->permissions, true) ?: [],
            fn (string $permission) => $permission !== 'missions.manage',
        ));

        DB::table('admin_access_roles')->where('key', 'operations')->update([
            'permissions' => json_encode($permissions),
            'updated_at' => now(),
        ]);
    }
};
