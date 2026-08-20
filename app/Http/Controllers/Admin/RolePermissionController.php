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

        return view('admin.roles.index', [
            'roles' => $roles,
            'roleCounts' => $roleCounts,
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

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionKeys)],
        ]);

        $selected = $validated['permissions'] ?? [];
        $required = config('admin.required_permissions', []);
        $permissions = array_values(array_intersect(
            $permissionKeys,
            array_unique([...$required, ...$selected]),
        ));

        $adminRole->update(['permissions' => $permissions]);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "{$adminRole->label} permissions updated successfully.");
    }

    private function orderedRoles()
    {
        $order = array_flip(array_keys(config('admin.roles', [])));

        return AdminRole::query()
            ->get()
            ->sortBy(fn (AdminRole $role) => $order[$role->getKey()] ?? PHP_INT_MAX)
            ->values();
    }
}
