@extends('admin.layouts.app')
@section('title', 'Edit User - LesGo Admin')
@section('header', 'Edit User')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    @foreach(['customer', 'driver', 'partner', 'admin'] as $role)
                        <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Access Level</label>
                <select id="admin-role-select" name="admin_role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Not applicable</option>
                    @foreach($adminRoles as $adminRole)
                        <option value="{{ $adminRole->key }}" @selected(old('admin_role', $user->effectiveAdminRole()) === $adminRole->key)>{{ $adminRole->label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Required only when the user role is Admin.</p>
            </div>

            @php
                $permissionGroups = collect(config('admin.permissions', []))->groupBy('group');
                $selectedPerms = old('admin_permissions', $user->admin_permissions ?? []);
                if (!is_array($selectedPerms)) $selectedPerms = [];
            @endphp
            <div id="admin-permissions-section" class="mb-6 {{ old('role', $user->role) !== 'admin' ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-2">Extra Permissions <span class="text-xs font-normal text-gray-500">(on top of role)</span></label>
                <p class="text-xs text-gray-500 mb-3">Select additional permissions for this admin. Super Admin always has full access — extra perms are ignored.</p>
                <div class="space-y-4 max-h-[32rem] overflow-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                    @foreach($permissionGroups as $group => $perms)
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">{{ $group }}</p>
                            <div class="grid sm:grid-cols-2 gap-2">
                                @foreach($perms as $key => $perm)
                                    <label class="flex gap-2 bg-white rounded-lg border border-gray-200 p-2.5 cursor-pointer hover:bg-blue-50/50">
                                        <input type="checkbox" name="admin_permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPerms)) class="admin-perm-checkbox mt-0.5 rounded border-gray-300 text-blue-600" data-group="{{ $group }}">
                                        <span>
                                            <span class="block text-xs font-medium text-gray-800">{{ $perm['label'] }}</span>
                                            <span class="block text-[11px] leading-tight text-gray-500">{{ $perm['description'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <p id="super-admin-note" class="hidden mt-2 text-xs text-purple-600">Super Admin has * — extra permissions not needed.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                @if($user->profile_picture)
                    <img src="{{ \Illuminate\Support\Str::startsWith($user->profile_picture, ['http://','https://']) ? $user->profile_picture : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($user->profile_picture) }}" alt="Profile" class="w-16 h-16 rounded-full object-cover mb-2 border">
                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="remove_profile_picture" value="1"> Remove current</label>
                @endif
                <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.webp" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 mt-2">
                <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP, max 2MB</p>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.users.show', $user) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const roleSelect = document.querySelector('select[name="role"]');
    const adminRoleSelect = document.getElementById('admin-role-select');
    const permSection = document.getElementById('admin-permissions-section');
    const superNote = document.getElementById('super-admin-note');
    const permCheckboxes = [...document.querySelectorAll('.admin-perm-checkbox')];
    const refresh = () => {
        const isAdmin = roleSelect?.value === 'admin';
        permSection?.classList.toggle('hidden', !isAdmin);
        const isSuper = adminRoleSelect?.value === 'super_admin';
        superNote?.classList.toggle('hidden', !isSuper || !isAdmin);
        permCheckboxes.forEach(cb => {
            cb.disabled = isSuper && isAdmin;
            cb.closest('label')?.classList.toggle('opacity-50', isSuper && isAdmin);
        });
    };
    roleSelect?.addEventListener('change', refresh);
    adminRoleSelect?.addEventListener('change', refresh);
    refresh();
})();
</script>
@endsection
