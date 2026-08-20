<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|in:customer,driver,partner,admin',
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::exists('admin_roles', 'key')],
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validated['role'] !== 'admin') {
            $validated['admin_role'] = null;
        }

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

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|in:customer,driver,partner,admin',
            'admin_role' => ['nullable', 'required_if:role,admin', Rule::exists('admin_roles', 'key')],
            'is_active' => 'boolean',
        ]);

        if ($validated['role'] !== 'admin') {
            $validated['admin_role'] = null;
        }

        if ($user->is($request->user()) && ($validated['role'] !== 'admin' || ! $request->boolean('is_active'))) {
            return back()->withInput()->withErrors(['role' => 'You cannot remove your own admin access or deactivate your own account.']);
        }

        if ($this->wouldRemoveLastSuperAdmin($user, $validated['role'], $validated['admin_role'], $request->boolean('is_active'))) {
            return back()->withInput()->withErrors(['admin_role' => 'At least one active super administrator must remain.']);
        }

        $user->update($validated);

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

        return AdminRole::query()
            ->get()
            ->sortBy(fn (AdminRole $role) => $order[$role->getKey()] ?? PHP_INT_MAX)
            ->values();
    }
}
