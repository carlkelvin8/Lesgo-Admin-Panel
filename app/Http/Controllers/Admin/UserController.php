<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Traits\SearchEscaping;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use SearchEscaping;

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['driverProfile', 'orders' => function ($q) {
            $q->latest()->take(10);
        }]);

        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        return view('admin.users.create', ['adminRoles' => $this->orderedAdminRoles()]);
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        if ($validated['role'] !== 'admin') {
            $validated['admin_role'] = null;
            $validated['admin_permissions'] = null;
        } else {
            $permissionKeys = array_keys(config('admin.permissions', []));
            $perms = array_values(array_unique(array_intersect($permissionKeys, $validated['admin_permissions'] ?? [])));
            $validated['admin_permissions'] = empty($perms) ? null : $perms;
            if (($validated['admin_role'] ?? null) === 'super_admin') {
                $validated['admin_permissions'] = null;
            }
        }

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', config('filesystems.default') === 's3' ? 's3' : 'public');
        } else {
            unset($validated['profile_picture']);
        }
        unset($validated['remove_profile_picture']);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'adminRoles' => $this->orderedAdminRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        if ($validated['role'] !== 'admin') {
            $validated['admin_role'] = null;
            $validated['admin_permissions'] = null;
        } else {
            // Normalize admin_permissions: only keep valid keys, de-dupe, null if empty
            if (array_key_exists('admin_permissions', $validated)) {
                $permissionKeys = array_keys(config('admin.permissions', []));
                $perms = array_values(array_unique(array_intersect($permissionKeys, $validated['admin_permissions'] ?? [])));
                $validated['admin_permissions'] = empty($perms) ? null : $perms;
            }
            // Super admin always has '*' – don't store extra perms that confuse hasAdminPermission
            if (($validated['admin_role'] ?? $user->admin_role) === 'super_admin') {
                $validated['admin_permissions'] = null;
            }
        }

        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($user->profile_picture); } catch (\Throwable $e) {}
            }
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', config('filesystems.default') === 's3' ? 's3' : 'public');
        } elseif ($request->boolean('remove_profile_picture')) {
            if ($user->profile_picture) {
                try { Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($user->profile_picture); } catch (\Throwable $e) {}
            }
            $validated['profile_picture'] = null;
        } else {
            unset($validated['profile_picture']);
        }
        unset($validated['remove_profile_picture']);

        if ($user->is($request->user()) && ($validated['role'] !== 'admin' || ! $request->boolean('is_active'))) {
            return back()->withInput()->withErrors(['role' => 'You cannot remove your own admin access or deactivate your own account.']);
        }

        if ($this->wouldRemoveLastSuperAdmin($user, $validated['role'], $validated['admin_role'], $request->boolean('is_active'))) {
            return back()->withInput()->withErrors(['admin_role' => 'At least one active super administrator must remain.']);
        }

        $oldPerms = $user->admin_permissions;
        $user->update($validated);

        // Audit max level
        try {
            if (($oldPerms ?? null) !== ($validated['admin_permissions'] ?? null) || ($user->wasChanged('admin_role'))) {
                \App\Models\AuditLog::create([
                    'user_id' => $request->user()?->id,
                    'action' => 'update_user_permissions',
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'old_values' => ['admin_role' => $user->getOriginal('admin_role'), 'admin_permissions' => $oldPerms],
                    'new_values' => ['admin_role' => $validated['admin_role'] ?? null, 'admin_permissions' => $validated['admin_permissions'] ?? null],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Throwable $e) {}

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->is(auth()->user()) && $user->is_active) {
            return back()->withErrors(['user' => 'You cannot deactivate your own admin account.']);
        }

        if ($user->is_active && $this->wouldRemoveLastSuperAdmin($user, $user->role, $user->admin_role, false)) {
            return back()->withErrors(['user' => 'The last active super administrator cannot be deactivated.']);
        }

        $newStatus = ! $user->is_active;
        $user->update([
            'is_active' => $newStatus,
            'deactivated_at' => $newStatus ? null : now(),
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "User {$status} successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot delete your own admin account.']);
        }

        if ($this->wouldRemoveLastSuperAdmin($user, $user->role, $user->admin_role, false)) {
            return back()->withErrors(['user' => 'The last active super administrator cannot be deleted.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $users = User::query()->whereIn('id', $validated['ids'])->get();
        $deleted = 0;
        $skipped = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($users, &$deleted, &$skipped) {
            foreach ($users as $user) {
                // Bulk deletion is intentionally limited to non-admin accounts.
                if ($user->isAdmin() || $user->is(auth()->user())) {
                    $skipped++;
                    continue;
                }

                $user->delete();
                $deleted++;
            }
        });

        $message = "{$deleted} user(s) deleted.";
        if ($skipped > 0) {
            $message .= " {$skipped} protected admin account(s) skipped.";
        }

        return back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export_' . now()->format('Y-m-d_His') . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Admin Role', 'Status', 'Joined']);

            $query->latest()->chunk(500, function ($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->phone_number ?? '',
                        $user->role,
                        $user->admin_role ?? '',
                        $user->is_active ? 'Active' : 'Inactive',
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function wouldRemoveLastSuperAdmin(User $user, string $role, ?string $adminRole, bool $isActive): bool
    {
        if (! $user->isAdmin() || ! $user->isSuperAdmin() || ! $user->is_active) {
            return false;
        }

        if ($role === 'admin' && ($adminRole ?: 'super_admin') === 'super_admin' && $isActive) {
            return false;
        }

        return User::query()
            ->where('role', 'admin')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('admin_role', 'super_admin')->orWhereNull('admin_role');
            })
            ->count() <= 1;
    }

    private function orderedAdminRoles()
    {
        $order = array_flip(array_keys(config('admin.roles', [])));

        return AdminRole::definitions()
            ->sortBy(fn (AdminRole $role) => $order[$role->getKey()] ?? PHP_INT_MAX)
            ->values();
    }
}
