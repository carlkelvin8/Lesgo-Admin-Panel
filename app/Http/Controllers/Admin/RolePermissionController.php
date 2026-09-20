<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = $this->orderedRoles();
        $roleCounts = User::query()
            ->where('role', 'admin')
            ->get(['admin_role'])
            ->countBy(fn (User $user) => $user->effectiveAdminRole());

        $adminsByRole = User::query()
            ->where('role', 'admin')
            ->get(['id', 'name', 'email', 'admin_role', 'is_active'])
            ->groupBy(fn (User $user) => $user->effectiveAdminRole());

        return view('admin.roles.index', [
            'roles' => $roles,
            'roleCounts' => $roleCounts,
            'adminsByRole' => $adminsByRole,
            'permissionCount' => count(config('admin.permissions', [])),
        ]);
    }

    public function edit(AdminRole $adminRole)
    {
        $roleUserCount = User::query()
            ->where('role', 'admin')
            ->when(
                $adminRole->getKey() === 'super_admin',
                fn ($query) => $query->where(fn ($roles) => $roles
                    ->where('admin_role', 'super_admin')
                    ->orWhereNull('admin_role')),
                fn ($query) => $query->where('admin_role', $adminRole->getKey()),
            )
            ->count();

        return view('admin.roles.edit', [
            'adminRole' => $adminRole,
            'permissionGroups' => collect(config('admin.permissions', []))->groupBy('group'),
            'requiredPermissions' => config('admin.required_permissions', []),
            'roleUserCount' => $roleUserCount,
        ]);
    }

    public function update(Request $request, AdminRole $adminRole)
    {
        abort_if($adminRole->is_protected, 403, 'Protected administrator roles cannot be changed.');

        $permissionKeys = array_keys(config('admin.permissions', []));

        // MAX LEVEL: permissive validation — never throw "permissions.0 is invalid", just filter
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:100'],
        ]);

        $raw = $validated['permissions'] ?? [];
        // Log any invalid keys for debugging but don't fail
        $invalid = array_diff($raw, $permissionKeys);
        if (!empty($invalid)) {
            \Illuminate\Support\Facades\Log::warning('Role permissions: ignoring invalid keys', ['role' => $adminRole->getKey(), 'invalid' => $invalid, 'validKeys' => $permissionKeys]);
        }
        $selected = array_values(array_intersect($permissionKeys, $raw));
        $required = config('admin.required_permissions', []);
        // MAX LEVEL: always keep required, filter valid, sort by config order, de-dupe
        $permissions = array_values(array_intersect(
            $permissionKeys,
            array_unique([...$required, ...$selected]),
        ));

        $old = $adminRole->permissions ?? [];
        // Use direct DB update so a stale Eloquent snapshot can never hide the write.
        \Illuminate\Support\Facades\DB::table('admin_access_roles')
            ->where('key', $adminRole->getKey())
            ->update(['permissions' => json_encode($permissions), 'updated_at' => now()]);
        $adminRole->refresh();
        // Force cache bust for all workers (static + shared) and stale config cache
        AdminRole::forgetDefinitionCache();
        try { \Illuminate\Support\Facades\Cache::forget('admin:role_definitions:v2'); } catch (\Throwable $e) {}
        try { \Illuminate\Support\Facades\Artisan::call('config:clear'); } catch (\Throwable $e) {}
        \Illuminate\Support\Facades\Log::info('Role permissions updated', [
            'role' => $adminRole->getKey(),
            'old' => $old,
            'new' => $permissions,
            'count' => count($permissions),
            'by' => $request->user()?->id,
        ]);

        // Audit log — correct columns for this app's audit_logs table
        try {
            \App\Models\AuditLog::create([
                'user_id' => $request->user()?->id,
                'event_type' => 'admin_action',
                'event_category' => 'role_permissions',
                'action' => 'update_role_permissions',
                'description' => "Updated {$adminRole->label} permissions from ".count($old)." to ".count($permissions),
                'resource_type' => 'AdminRole',
                'resource_id' => null,
                'old_values' => ['permissions' => $old, 'role' => $adminRole->getKey()],
                'new_values' => ['permissions' => $permissions, 'role' => $adminRole->getKey()],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Role permissions audit failed', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "{$adminRole->label} permissions updated successfully. (" . count($permissions) . " permissions)");
    }

    private function orderedRoles()
    {
        $order = array_flip(array_keys(config('admin.roles', [])));

        return AdminRole::definitions()
            ->sortBy(fn (AdminRole $role) => $order[$role->getKey()] ?? PHP_INT_MAX)
            ->values();
    }
}
