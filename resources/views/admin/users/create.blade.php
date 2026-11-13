@extends('admin.layouts.app')
@section('title', 'Create User - LesGo Admin')
@section('header', 'Create User')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-name">Name</label>
                <input id="field-name" type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-email">Email</label>
                <input id="field-email" type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-phone_number">Phone Number</label>
                <input id="field-phone_number" type="text" name="phone_number" value="{{ old('phone_number') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-role">Role</label>
                <select id="field-role" name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Select Role</option>
                    @foreach(\App\Models\User::ROLES as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-admin_role">Admin Access Level</label>
                <select id="field-admin_role" name="admin_role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Not applicable</option>
                    @foreach($adminRoles as $adminRole)
                        <option value="{{ $adminRole->key }}" @selected(old('admin_role') === $adminRole->key)>{{ $adminRole->label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Required only when the user role is Admin.</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-password">Password</label>
                <input id="field-password" type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-password_confirmation">Confirm Password</label>
                <input id="field-password_confirmation" type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create User</button>
                <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
