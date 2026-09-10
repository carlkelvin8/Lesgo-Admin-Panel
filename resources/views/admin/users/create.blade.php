@extends('admin.layouts.app')
@section('title', 'Create User - LesGo Admin')
@section('header', 'Create User')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Select Role</option>
                    @foreach(['customer', 'driver', 'partner', 'admin'] as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Access Level</label>
                <select id="admin-role-select-create" name="admin_role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Not applicable</option>
                    @foreach($adminRoles as $adminRole)
                        <option value="{{ $adminRole->key }}" @selected(old('admin_role') === $adminRole->key)>{{ $adminRole->label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Required only when the user role is Admin.</p>
            </div>

            @php
                $permissionGroups = collect(config('admin.permissions', []))->groupBy('group');
                $selectedPerms = old('admin_permissions', []);
                if (!is_array($selectedPerms)) $selectedPerms = [];
            @endphp
            <div id="admin-permissions-section-create" class="mb-6 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Extra Permissions <span class="text-xs font-normal text-gray-500">(on top of role)</span></label>
                <p class="text-xs text-gray-500 mb-3">Select additional permissions for this admin. Super Admin always has full access.</p>
                <div class="space-y-4 max-h-[32rem] overflow-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                    @foreach($permissionGroups as $group => $perms)
                        <div>
                            <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">{{ $group }}</p>
                            <div class="grid sm:grid-cols-2 gap-2">
                                @foreach($perms as $key => $perm)
                                    <label class="flex gap-2 bg-white rounded-lg border border-gray-200 p-2.5 cursor-pointer hover:bg-blue-50/50">
                                        <input type="checkbox" name="admin_permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPerms)) class="admin-perm-checkbox-create mt-0.5 rounded border-gray-300 text-blue-600">
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
                <p id="super-admin-note-create" class="hidden mt-2 text-xs text-purple-600">Super Admin has * — extra permissions not needed.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture <span class="text-xs font-normal text-gray-500">(croppable to circle)</span></label>
                <x-admin.profile-picture-cropper :existing-url="null" input-id="profile_picture_input_create" hidden-input-id="cropped_profile_picture_create" preview-id="cropper-preview-img-create" />
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(() => {
    const roleSelect = document.querySelector('select[name="role"]');
    const adminRoleSelect = document.getElementById('admin-role-select-create');
    const permSection = document.getElementById('admin-permissions-section-create');
    const superNote = document.getElementById('super-admin-note-create');
    const cbs = [...document.querySelectorAll('.admin-perm-checkbox-create')];
    const refresh = () => {
        const isAdmin = roleSelect?.value === 'admin';
        permSection?.classList.toggle('hidden', !isAdmin);
        const isSuper = adminRoleSelect?.value === 'super_admin';
        superNote?.classList.toggle('hidden', !isSuper || !isAdmin);
        cbs.forEach(cb => { cb.disabled = isSuper && isAdmin; cb.closest('label')?.classList.toggle('opacity-50', isSuper && isAdmin); });
    };
    roleSelect?.addEventListener('change', refresh);
    adminRoleSelect?.addEventListener('change', refresh);
    refresh();
})();
</script>
@endsection
